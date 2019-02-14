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

namespace Apisearch\Plugin\Elastica\Domain\AppRepository;

use Apisearch\Config\Config;
use Apisearch\Exception\ResourceExistsException;
use Apisearch\Exception\ResourceNotAvailableException;
use Apisearch\Model\Index;
use Apisearch\Model\IndexUUID;
use Apisearch\Plugin\Elastica\Domain\ElasticaWrapperWithRepositoryReference;
use Apisearch\Server\Domain\Repository\AppRepository\IndexRepository as IndexRepositoryInterface;
use Elastica\Exception\ResponseException;

/**
 * Class IndexRepository.
 */
class IndexRepository extends ElasticaWrapperWithRepositoryReference implements IndexRepositoryInterface
{
    /**
     * Get indices.
     *
     * @return Index[]
     */
    public function getIndices(): array
    {
        return $this
            ->elasticaWrapper
            ->getIndices($this->getRepositoryReference());
    }

    /**
     * Create an index.
     *
     * @param IndexUUID $indexUUID
     * @param Config    $config
     *
     * @throws ResourceExistsException
     */
    public function createIndex(
        IndexUUID $indexUUID,
        Config $config
    ) {
        $repositoryReference = $this
            ->getRepositoryReference()
            ->changeIndex($indexUUID);

        $this
            ->elasticaWrapper
            ->createIndex(
                $repositoryReference,
                $config
            );

        $this
            ->elasticaWrapper
            ->createIndexMapping(
                $repositoryReference,
                $config
            );
    }

    /**
     * Delete the index.
     *
     * @param IndexUUID $indexUUID
     */
    public function deleteIndex(IndexUUID $indexUUID)
    {
        $this
            ->elasticaWrapper
            ->deleteIndex($this
                ->getRepositoryReference()
                ->changeIndex($indexUUID)
            );
    }

    /**
     * Reset the index.
     *
     * @param IndexUUID $indexUUID
     */
    public function resetIndex(IndexUUID $indexUUID)
    {
        $repositoryReference = $this
            ->getRepositoryReference()
            ->changeIndex($indexUUID);

        $this
            ->elasticaWrapper
            ->resetIndex($repositoryReference);

        $this->refresh($repositoryReference);
    }

    /**
     * Configure the index.
     *
     * @param IndexUUID $indexUUID
     * @param Config    $config
     *
     * @throws ResourceNotAvailableException
     */
    public function configureIndex(
        IndexUUID $indexUUID,
        Config $config
    ) {
        $repositoryReference = $this
            ->getRepositoryReference()
            ->changeIndex($indexUUID);

        $this
            ->elasticaWrapper
            ->configureIndex(
                $repositoryReference,
                $config
            );

        $this->refresh($repositoryReference);
    }

    /**
     * Get the index stats.
     *
     * @param IndexUUID $indexUUID
     *
     * @return bool
     */
    public function isIndexOK(IndexUUID $indexUUID): bool
    {
        try {
            $indices = $this
                ->elasticaWrapper
                ->getIndices($this
                    ->getRepositoryReference()
                    ->changeIndex($indexUUID)
                );
        } catch (ResponseException $exception) {
            return false;
        }

        return (bool) array_reduce($indices, function ($carry, Index $index) {
            return $carry && $index->isOK();
        }, true);
    }
}
