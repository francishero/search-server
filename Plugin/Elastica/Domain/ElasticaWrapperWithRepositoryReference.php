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

use Apisearch\Repository\RepositoryReference;
use Apisearch\Repository\WithRepositoryReference;
use Apisearch\Repository\WithRepositoryReferenceTrait;

/**
 * Class ElasticaWithAppIdWrapper.
 */
abstract class ElasticaWrapperWithRepositoryReference implements WithRepositoryReference
{
    use WithRepositoryReferenceTrait;

    /**
     * @var ItemElasticaWrapper
     *
     * Elastica wrapper
     */
    protected $elasticaWrapper;

    /**
     * @var string
     *
     * Repository config path
     */
    protected $repositoryConfigPath;

    /**
     * @var bool
     *
     * Refresh on write
     */
    protected $refreshOnWrite;

    /**
     * ElasticaSearchRepository constructor.
     *
     * @param ItemElasticaWrapper $elasticaWrapper
     * @param string              $repositoryConfigPath
     * @param bool                $refreshOnWrite
     */
    public function __construct(
        ItemElasticaWrapper $elasticaWrapper,
        string $repositoryConfigPath,
        bool $refreshOnWrite
    ) {
        $this->elasticaWrapper = $elasticaWrapper;
        $this->repositoryConfigPath = $repositoryConfigPath;
        $this->refreshOnWrite = $refreshOnWrite;
    }

    /**
     * Refresh.
     *
     * @param RepositoryReference $repositoryReference
     */
    protected function refresh(RepositoryReference $repositoryReference)
    {
        $this
            ->elasticaWrapper
            ->refresh($repositoryReference);
    }

    /**
     * Get config path.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return string
     */
    protected function getConfigPath(RepositoryReference $repositoryReference): string
    {
        return rtrim(str_replace(
            ['{app_id}', '{index_id}'],
            [
                $repositoryReference->getAppUUID()
                    ? $repositoryReference
                        ->getAppUUID()
                        ->composeUUID()
                    : '',
                $repositoryReference->getIndexUUID()
                    ? $repositoryReference
                        ->getIndexUUID()
                        ->composeUUID()
                    : '',
            ],
            $this->repositoryConfigPath
        ), '/');
    }

    /**
     * Normalize Repository Reference for cross index.
     *
     * @param RepositoryReference $repositoryReference
     *
     * @return RepositoryReference
     */
    protected function normalizeRepositoryReferenceCrossIndices(RepositoryReference $repositoryReference)
    {
        if (is_null($repositoryReference->getIndexUUID())) {
            return $repositoryReference;
        }

        $indices = $repositoryReference
            ->getIndexUUID()
            ->composeUUID();

        $appUUIDComposed = $repositoryReference
            ->getAppUUID()
            ->composeUUID();

        if ('*' === $indices) {
            return RepositoryReference::create(
                $appUUIDComposed,
                'all'
            );
        }

        $splittedIndices = explode(',', $indices);
        if (count($splittedIndices) > 1) {
            sort($splittedIndices);

            return RepositoryReference::create(
                $appUUIDComposed,
                implode('_', $splittedIndices)
            );
        }

        return $repositoryReference;
    }
}
