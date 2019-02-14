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

namespace Apisearch\Plugin\Elastica\Domain\Repository;

use Apisearch\Model\ItemUUID;
use Apisearch\Plugin\Elastica\Domain\ElasticaWrapperWithRepositoryReference;
use Apisearch\Server\Domain\Repository\Repository\DeleteRepository as DeleteRepositoryInterface;

/**
 * Class DeleteRepository.
 */
class DeleteRepository extends ElasticaWrapperWithRepositoryReference implements DeleteRepositoryInterface
{
    /**
     * Delete items.
     *
     * @param ItemUUID[] $itemUUIDs
     */
    public function deleteItems(array $itemUUIDs)
    {
        $repositoryReference = $this->getRepositoryReference();
        $this
            ->elasticaWrapper
            ->deleteDocumentsByIds(
                $repositoryReference,
                array_map(function (ItemUUID $itemUUID) {
                    return $itemUUID->composeUUID();
                }, $itemUUIDs)
            );

        if ($this->refreshOnWrite) {
            $this->refresh($repositoryReference);
        }
    }
}
