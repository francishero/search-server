<?php

/*
 * This file is part of the Apisearch Server
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Apisearch\Plugin\Elastica\Domain;

use Apisearch\Config\Config;
use Apisearch\Config\Synonym;
use Apisearch\Exception\ResourceExistsException;
use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Model\AppUUID;
use Apisearch\Model\Index as ApisearchIndex;
use Apisearch\Model\IndexUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Exception\ParsedCreatingIndexException;
use Apisearch\Server\Exception\ParsedResourceNotAvailableException;
use Elastica\Client;
use Elastica\Document;
use Elastica\Exception\Bulk\ResponseException as BulkResponseException;
use Elastica\Exception\ResponseException;
use Elastica\Index;
use Elastica\Query;
use Elastica\Type;
use Elasticsearch\Endpoints\Cat\Aliases;
use Elasticsearch\Endpoints\Cat\Indices;
use Elasticsearch\Endpoints\Indices\Mapping as MappingEndpoint;
use Elasticsearch\Endpoints\Reindex;
use Elasticsearch\Endpoints\Tasks\Get as Task;

/**
 * Class ItemElasticaWrapper.
 */
class ItemElasticaWrapper
{
    /**
     * @var string
     *
     * Item type
     */
    const ITEM_TYPE = 'item';

    /**
     * @var Client
     *
     * Elastica client
     */
    private $client;

    /**
     * Construct.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get index prefix.
     *
     * @return string
     */
    public function getAliasPrefix(): string
    {
        return 'apisearch_item';
    }

    /**
     * Get index prefix.
     *
     * @return string
     */
    public function generateRandomIndexPrefix(): string
    {
        $randomID = rand(100000000000, 1000000000000);

        return "apisearch_{$randomID}_item";
    }

    /**
     * Get random index name.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return string
     */
    public function getRandomIndexName(RepositoryReference $repositoryReference): string
    {
        return $this->buildIndexReference(
            $repositoryReference,
            $this->generateRandomIndexPrefix()
        );
    }

    /**
     * Get index alias name.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return string
     */
    public function getIndexAliasName(RepositoryReference $repositoryReference): string
    {
        return $this->buildIndexReference(
            $repositoryReference,
            $this->getAliasPrefix()
        );
    }

    /**
     * Get index not available exception.
     *
     * @param string $message
     *
     * @return ResourceNotAvailableException
     */
    public function getIndexNotAvailableException(string $message): ResourceNotAvailableException
    {
        return ParsedResourceNotAvailableException::parsedIndexNotAvailable($message);
    }

    /**
     * Get index configuration.
     *
     * @param Config $config
     *
     * @return array
     */
    public function getImmutableIndexConfiguration(Config $config): array
    {
        $language = $config->getLanguage();

        $defaultAnalyzerFilter = [
            5 => 'lowercase',
            20 => 'asciifolding',
            50 => 'ngram_filter',
        ];

        $searchAnalyzerFilter = [
            5 => 'lowercase',
            50 => 'asciifolding',
        ];

        $indexConfiguration = [
            'number_of_shards' => $config->getShards(),
            'number_of_replicas' => $config->getReplicas(),
            'max_result_window' => 50000,
            'analysis' => [
                'analyzer' => [
                    'default' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => [],
                    ],
                    'search_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => [],
                    ],
                ],
                'filter' => [
                    'ngram_filter' => [
                        'type' => 'edge_ngram',
                        'min_gram' => 1,
                        'max_gram' => 20,
                        'token_chars' => [
                            'letter',
                        ],
                    ],
                ],
                'normalizer' => [
                    'exact_matching_normalizer' => [
                        'type' => 'custom',
                        'filter' => [
                            'lowercase',
                            'asciifolding',
                        ],
                    ],
                ],
            ],
        ];

        $stopWordsLanguage = ElasticaLanguages::getStopwordsLanguageByIso($language);
        if (!is_null($stopWordsLanguage)) {
            $defaultAnalyzerFilter[30] = 'stop_words';
            $searchAnalyzerFilter[30] = 'stop_words';
            $indexConfiguration['analysis']['filter']['stop_words'] = [
                'type' => 'stop',
                'stopwords' => $stopWordsLanguage,
            ];
        }

        $stemmer = ElasticaLanguages::getStemmerLanguageByIso($language);
        if (!is_null($stemmer)) {
            $searchAnalyzerFilter[35] = 'stemmer';
            $indexConfiguration['analysis']['filter']['stemmer'] = [
                'type' => 'stemmer',
                'name' => $stemmer,
            ];
        }

        $synonyms = $config->getSynonyms();
        if (!empty($synonyms)) {
            $defaultAnalyzerFilter[40] = 'synonym';
            $indexConfiguration['analysis']['filter']['synonym'] = [
                'type' => 'synonym',
                'synonyms' => array_map(function (Synonym $synonym) {
                    return strtolower($synonym->expand());
                }, $synonyms),
            ];
        }

        ksort($defaultAnalyzerFilter, SORT_NUMERIC);
        ksort($searchAnalyzerFilter, SORT_NUMERIC);
        $indexConfiguration['analysis']['analyzer']['default']['filter'] = array_values($defaultAnalyzerFilter);
        $indexConfiguration['analysis']['analyzer']['search_analyzer']['filter'] = array_values($searchAnalyzerFilter);

        return $indexConfiguration;
    }

    /**
     * Build index mapping.
     *
     * @param Type\Mapping $mapping
     * @param Config       $config
     */
    public function buildIndexMapping(
        Type\Mapping $mapping,
        Config $config
    ) {
        $mapping->setParam('dynamic_templates', [
            [
                'dynamic_metadata_as_keywords' => [
                    'path_match' => 'indexed_metadata.*',
                    'match_mapping_type' => 'string',
                    'mapping' => [
                        'type' => 'keyword',
                    ],
                ],
            ],
            [
                'dynamic_searchable_metadata_as_text' => [
                    'path_match' => 'searchable_metadata.*',
                    'mapping' => [
                        'type' => 'text',
                        'analyzer' => 'default',
                        'search_analyzer' => 'search_analyzer',
                    ],
                ],
            ],
            [
                'dynamic_arrays_as_nested' => [
                    'path_match' => 'indexed_metadata.*',
                    'match_mapping_type' => 'object',
                    'mapping' => [
                        'type' => 'nested',
                    ],
                ],
            ],
        ]);

        $sourceExcludes = [];
        if (!$config->shouldSearchableMetadataBeStored()) {
            $sourceExcludes = [
                'searchable_metadata',
                'exact_matching_metadata',
            ];
        }

        $mapping->setSource(['excludes' => $sourceExcludes]);

        $mapping->setProperties([
            'uuid' => [
                'type' => 'object',
                'dynamic' => 'strict',
                'properties' => [
                    'id' => [
                        'type' => 'keyword',
                    ],
                    'type' => [
                        'type' => 'keyword',
                    ],
                ],
            ],
            'coordinate' => ['type' => 'geo_point'],
            'metadata' => [
                'type' => 'object',
                'dynamic' => true,
                'enabled' => false,
            ],
            'indexed_metadata' => [
                'type' => 'object',
                'dynamic' => true,
            ],
            'searchable_metadata' => [
                'type' => 'object',
                'dynamic' => true,
            ],
            'exact_matching_metadata' => [
                'type' => 'keyword',
                'normalizer' => 'exact_matching_normalizer',
            ],
            'suggest' => [
                'type' => 'completion',
                'analyzer' => 'search_analyzer',
                'search_analyzer' => 'search_analyzer',
            ],
        ]);
    }

    /**
     * Get search index.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return Index
     */
    public function getIndex(RepositoryReference $repositoryReference): Index
    {
        $indexAliasName = $this->getIndexAliasName($repositoryReference);

        return $this
            ->client
            ->getIndex($indexAliasName);
    }

    /**
     * Get indices.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return ApisearchIndex[]
     */
    public function getIndices(RepositoryReference $repositoryReference): array
    {
        $appUUIDComposed = $repositoryReference->getAppUUID() instanceof AppUUID
            ? $repositoryReference
                ->getAppUUID()
                ->composeUUID()
            : null;

        $indexUUIDComposed = $repositoryReference->getIndexUUID() instanceof IndexUUID
            ? $repositoryReference
                ->getIndexUUID()
                ->composeUUID()
            : null;

        $indexUUIDComposedMultiple = is_string($indexUUIDComposed)
            ? explode(',', $indexUUIDComposed)
            : [null];

        $indexPrefix = $this->getAliasPrefix();
        $indexSearchKeyword = array_map(function ($indexUUIDComposed) use ($indexPrefix, $appUUIDComposed) {
            return $indexPrefix.'_'.(
                empty($appUUIDComposed)
                    ? '*'
                    : $appUUIDComposed.'_'.(
                        empty($indexUUIDComposed)
                            ? '*'
                            : $indexUUIDComposed
                    )
                );
        }, $indexUUIDComposedMultiple);

        $elasticaResponse = $this->client->requestEndpoint((new Indices())->setIndex($indexSearchKeyword));
        $elasticaMappingResponse = $this->client->requestEndpoint((new MappingEndpoint\Get())->setIndex($indexSearchKeyword));
        $mappingData = $this->getMappingMetadataByResponse($elasticaMappingResponse->getData());

        if (empty($elasticaResponse->getData())) {
            return [];
        }

        $regexToParse = '/^'.
            '(?P<color>[^\ ]+)\s+'.
            '(?P<status>[^\ ]+)\s+'.
            '(?P<fullname>apisearch_\d+_item_(?P<app_id>[^_]+)_(?P<id>[^\ ]+))\s+'.
            '(?P<uuid>[^\ ]+)\s+'.
            '(?P<primary_shards>[^\ ]+)\s+'.
            '(?P<replica_shards>[^\ ]+)\s+'.
            '(?P<doc_count>[^\ ]+)\s+'.
            '(?P<doc_deleted>[^\ ]+)\s+'.
            '(?P<index_size>[^\ ]+)\s+'.
            '(?P<storage_size>[^\ ]+)'.
            '$/im';

        $indices = [];
        preg_match_all($regexToParse, $elasticaResponse->getData()['message'], $matches, PREG_SET_ORDER, 0);
        if ($matches) {
            foreach ($matches as $metaData) {
                $indices[] = new ApisearchIndex(
                    IndexUUID::createById($metaData['id']),
                    AppUUID::createById($metaData['app_id']),
                    (
                        'open' === $metaData['status'] &&
                        in_array($metaData['color'], ['green', 'yellow'])
                    ),
                    (int) $metaData['doc_count'],
                    (string) $metaData['index_size'],
                    (int) $metaData['primary_shards'],
                    (int) $metaData['replica_shards'],
                    $mappingData[$metaData['fullname']] ?? [],
                    [
                        'allocated' => ('green' === $metaData['color']),
                        'doc_deleted' => (int) $metaData['doc_deleted'],
                    ]
                );
            }
        }

        return $indices;
    }

    /**
     * Given a Mapping response, create metadata values per index.
     *
     * @param array $response
     *
     * @return array
     */
    private function getMappingMetadataByResponse(array $response): array
    {
        $metadataData = [];
        foreach ($response as $indexId => $metadataValues) {
            if (!isset($metadataValues['mappings']['item'])) {
                continue;
            }

            $metadataBucket = [];
            $this->getMappingProperties(
                $metadataBucket,
                '',
                $metadataValues['mappings']['item']
            );
            $metadataData[$indexId] = $metadataBucket;
        }

        return $metadataData;
    }

    /**
     * Get properties.
     *
     * @param array  $metadataBucket
     * @param string $field
     * @param array  $data
     */
    private function getMappingProperties(
        array &$metadataBucket,
        string $field,
        array $data
    ): void {
        if (isset($data['type'])) {
            $metadataBucket[$field] = $data['type'];

            return;
        }

        foreach ($data['properties'] ?? [] as $property => $value) {
            $this->getMappingProperties(
                $metadataBucket,
                trim("$field.$property", '.'),
                $value
            );
        }
    }

    /**
     * Get index stats.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return Index\Stats
     */
    public function getIndexStats(RepositoryReference $repositoryReference): Index\Stats
    {
        try {
            return $this
                ->client
                ->getIndex($this->getIndexAliasName($repositoryReference))
                ->getStats();
        } catch (ResponseException $exception) {
            /*
             * The index resource cannot be deleted.
             * This means that the resource is not available
             */
            throw $this->getIndexNotAvailableException($exception->getMessage());
        }
    }

    /**
     * Create index.
     *
     * @param RepositoryReference $repositoryReference
     * @param Config              $config
     *
     * @throws ResourceExistsException
     */
    public function createIndex(
        RepositoryReference $repositoryReference,
        Config $config
    ) {
        if (!is_null($this->getOriginalIndexName($repositoryReference))) {
            throw ResourceExistsException::indexExists();
        }

        $indexAliasName = $this->getIndexAliasName($repositoryReference);
        $indexName = $this->getRandomIndexName($repositoryReference);
        $searchIndex = $this
            ->client
            ->getIndex($indexName);

        try {
            $searchIndex->create(
                $this->getImmutableIndexConfiguration($config)
            );
            $searchIndex->addAlias($indexAliasName);
        } catch (ResponseException $exception) {
            throw ParsedCreatingIndexException::parse($exception->getMessage());
        }
    }

    /**
     * Delete index.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @throws ResourceNotAvailableException
     */
    public function deleteIndex(RepositoryReference $repositoryReference)
    {
        try {
            $originalIndexName = $this->getOriginalIndexName($repositoryReference);
            if (is_null($originalIndexName)) {
                throw ResourceNotAvailableException::indexNotAvailable($repositoryReference->compose());
            }

            $indexAliasName = $this->getIndexAliasName($repositoryReference);
            $indexOriginalName = $this->getOriginalIndexName($repositoryReference);
            $searchIndex = $this
                ->client
                ->getIndex($indexOriginalName);
            $searchIndex->removeAlias($indexAliasName);
            $searchIndex->clearCache();
            $searchIndex->delete();
        } catch (ResponseException $exception) {
            /*
             * The index resource cannot be deleted.
             * This means that the resource is not available
             */
            throw $this->getIndexNotAvailableException($exception->getMessage());
        }
    }

    /**
     * Remove index.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @throws ResourceNotAvailableException
     */
    public function resetIndex(RepositoryReference $repositoryReference)
    {
        try {
            $indexAliasName = $this->getIndexAliasName($repositoryReference);
            $searchIndex = $this
                ->client
                ->getIndex($indexAliasName);

            $searchIndex->clearCache();
            $searchIndex->deleteByQuery(new Query\MatchAll());
        } catch (ResponseException $exception) {
            /*
             * The index resource cannot be deleted.
             * This means that the resource is not available
             */
            throw $this->getIndexNotAvailableException($exception->getMessage());
        }
    }

    /**
     * Configure index.
     *
     * @param RepositoryReference $repositoryReference
     * @param Config              $config
     *
     * @throws ResourceExistsException
     */
    public function configureIndex(
        RepositoryReference $repositoryReference,
        Config $config
    ) {
        $indexAliasName = $this->getIndexAliasName($repositoryReference);
        $indexOriginalOldName = $this->getOriginalIndexName($repositoryReference);
        $indexOriginalNewName = $this->getRandomIndexName($repositoryReference);

        $oldIndex = $this
            ->client
            ->getIndex($indexOriginalOldName);

        $newIndex = $this
            ->client
            ->getIndex($indexOriginalNewName);

        $newIndex->create(
            $this->getImmutableIndexConfiguration($config)
        );

        $this->createIndexMappingByIndexName(
            $indexOriginalNewName,
            $config
        );

        $reindex = new Reindex();
        $reindex->setParams(['wait_for_completion' => false]);
        $reindex->setBody([
            'source' => [
                'index' => $indexOriginalOldName,
            ],
            'dest' => [
                'index' => $indexOriginalNewName,
            ],
        ]);

        $result = $this
            ->client
            ->requestEndpoint($reindex);

        $taskId = $result->getData()['task'];
        $task = new Task();
        $task->setTaskId($taskId);

        while (true) {
            $taskResponse = $this
                ->client
                ->requestEndpoint($task);

            $taskResponseData = $taskResponse->getData();
            if (true === $taskResponseData['completed']) {
                break;
            }

            sleep(1);
        }

        $oldIndex->removeAlias($indexAliasName);
        $newIndex->addAlias($indexAliasName);
        $oldIndex->clearCache();
        $oldIndex->delete();
    }

    /**
     * Get item type by index name.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return Type
     */
    public function getItemTypeByRepositoryReference(RepositoryReference $repositoryReference): Type
    {
        return $this
            ->getIndex($repositoryReference)
            ->getType(self::ITEM_TYPE);
    }

    /**
     * Get item type by index name.
     *
     * @param string $indexName
     *
     * @return Type
     */
    public function getItemTypeByIndexName(string $indexName): Type
    {
        return $this
            ->client
            ->getIndex($indexName)
            ->getType(self::ITEM_TYPE);
    }

    /**
     * Search.
     *
     * @param RepositoryReference $repositoryReference
     * @param Query               $query
     * @param int                 $from
     * @param int                 $size
     *
     * @return array
     */
    public function search(
        RepositoryReference $repositoryReference,
        Query $query,
        int $from,
        int $size
    ): array {
        try {
            $queryResult = $this
                ->getIndex($repositoryReference)
                ->search($query, [
                    'from' => $from,
                    'size' => $size,
                ]);
        } catch (ResponseException $exception) {
            /*
             * The index resource cannot be deleted.
             * This means that the resource is not available
             */

            throw $this->getIndexNotAvailableException($exception->getMessage());
        }

        return [
            'results' => $queryResult->getResults(),
            'suggests' => $queryResult->getSuggests(),
            'aggregations' => $queryResult->getAggregations(),
            'total_hits' => $queryResult->getTotalHits(),
        ];
    }

    /**
     * Refresh.
     *
     * @param RepositoryReference $repositoryReference
     */
    public function refresh(RepositoryReference $repositoryReference)
    {
        $this
            ->getIndex($repositoryReference)
            ->refresh();
    }

    /**
     * Create mapping.
     *
     * @param RepositoryReference $repositoryReference
     * @param Config              $config
     *
     * @throws ResourceExistsException
     */
    public function createIndexMapping(
        RepositoryReference $repositoryReference,
        Config $config
    ) {
        $this->createIndexMappingByIndexName(
            $this->getIndexAliasName($repositoryReference),
            $config
        );
    }

    /**
     * Create index mapping by index name.
     *
     * @param string $indexName
     * @param Config $config
     *
     * @throws ResourceExistsException
     */
    public function createIndexMappingByIndexName(
        string $indexName,
        Config $config
    ) {
        try {
            $itemMapping = new Type\Mapping();
            $itemMapping->setType($this->getItemTypeByIndexName($indexName));
            $this->buildIndexMapping($itemMapping, $config);
            $itemMapping->send();
        } catch (ResponseException $exception) {
            /*
             * The index resource cannot be deleted.
             * This means that the resource is not available
             */
            throw $this->getIndexNotAvailableException($exception->getMessage());
        }
    }

    /**
     * Add documents.
     *
     * @param RepositoryReference $repositoryReference
     * @param Document[]          $documents
     *
     * @throws ResourceExistsException
     */
    public function addDocuments(
        RepositoryReference $repositoryReference,
        array $documents
    ) {
        try {
            $this
                ->getItemTypeByRepositoryReference($repositoryReference)
                ->addDocuments($documents);
        } catch (BulkResponseException $exception) {
            /*
             * The index resource cannot be deleted.
             * This means that the resource is not available
             */
            throw $this->getIndexNotAvailableException($exception->getMessage());
        }
    }

    /**
     * Delete documents by its.
     *
     * @param RepositoryReference $repositoryReference
     * @param string[]            $documentsId
     *
     * @throws ResourceExistsException
     */
    public function deleteDocumentsByIds(
        RepositoryReference $repositoryReference,
        array $documentsId
    ) {
        try {
            $this
                ->getItemTypeByRepositoryReference($repositoryReference)
                ->deleteByQuery(new Query\Ids(array_values($documentsId)));
        } catch (ResponseException $exception) {
            /*
             * The index resource cannot be deleted.
             * This means that the resource is not available
             */
            throw $this->getIndexNotAvailableException($exception->getMessage());
        }
    }

    /**
     * Build specific index reference.
     *
     * @param RepositoryReference $repositoryReference
     * @param string              $prefix
     *
     * @return string
     */
    protected function buildIndexReference(
        RepositoryReference $repositoryReference,
        string $prefix
    ) {
        if (is_null($repositoryReference->getAppUUID())) {
            return '';
        }

        $appId = $repositoryReference->getAppUUID()->composeUUID();
        if (is_null($repositoryReference->getIndexUUID())) {
            return "{$prefix}_{$appId}";
        }

        $indexId = $repositoryReference->getIndexUUID()->composeUUID();
        if ('*' === $indexId) {
            return "{$prefix}_{$appId}_*";
        }

        $splittedIndexId = explode(',', $indexId);

        return implode(',', array_map(function (string $indexId) use ($prefix, $appId) {
            return trim("{$prefix}_{$appId}_$indexId", '_ ');
        }, $splittedIndexId));
    }

    /**
     * Get original generated index name.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return string|null
     */
    private function getOriginalIndexName(RepositoryReference $repositoryReference): ? string
    {
        $appId = $repositoryReference->getAppUUID()->composeUUID();
        $indexId = $repositoryReference->getIndexUUID()->composeUUID();
        $aliases = new Aliases();
        $aliases->setName($this->getIndexAliasName($repositoryReference));
        $elasticaResponse = $this->client->requestEndpoint($aliases);
        $regexToParse = "~apisearch_item_{$appId}_{$indexId}\\s*(?P<index_name>apisearch_\\d*_item_{$appId}_{$indexId})~";
        if (empty($elasticaResponse->getData())) {
            return null;
        }

        preg_match($regexToParse, $elasticaResponse->getData()['message'], $match);

        return $match['index_name'] ?? null;
    }
}
