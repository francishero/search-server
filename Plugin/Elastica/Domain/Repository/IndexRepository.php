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

use Apisearch\Model\Coordinate;
use Apisearch\Model\Item;
use Apisearch\Plugin\Elastica\Domain\ElasticaWrapperWithRepositoryReference;
use Apisearch\Server\Domain\Repository\Repository\IndexRepository as IndexRepositoryInterface;
use Elastica\Document;
use Elastica\Document as ElasticaDocument;

/**
 * Class IndexRepository.
 */
class IndexRepository extends ElasticaWrapperWithRepositoryReference implements IndexRepositoryInterface
{
    /**
     * Generate items documents.
     *
     * @param Item[] $items
     */
    public function addItems(array $items)
    {
        $documents = [];
        foreach ($items as $item) {
            $documents[] = $this->createItemDocument($item);
        }

        if (empty($documents)) {
            return;
        }

        $repositoryReference = $this->getRepositoryReference();
        $this
            ->elasticaWrapper
            ->addDocuments(
                $repositoryReference,
                $documents
            );

        if ($this->refreshOnWrite) {
            $this->refresh($repositoryReference);
        }
    }

    /**
     * Create item document.
     *
     * @param Item $item
     *
     * @return Document
     */
    private function createItemDocument(Item $item): Document
    {
        $uuid = $item->getUUID();
        $itemDocument = [
            'uuid' => [
                'id' => $uuid->getId(),
                'type' => $uuid->getType(),
            ],
            'coordinate' => $item->getCoordinate() instanceof Coordinate
                ? $item
                    ->getCoordinate()
                    ->toArray()
                : null,
            'metadata' => $this->filterElementRecursively(
                $item->getMetadata()
            ),
            'indexed_metadata' => $this->filterElementRecursively(
                $item->getIndexedMetadata()
            ),
            'searchable_metadata' => $this->filterSearchableElementRecursively(
                $item->getSearchableMetadata(),
                false
            ),
            'exact_matching_metadata' => array_values(
                $this->filterSearchableElementRecursively(
                    $item->getExactMatchingMetadata(),
                    false
                )
            ),
            'suggest' => array_values(
                $this->filterSearchableElementRecursively(
                    $item->getSuggest(),
                    false
                )
            ),
        ];

        return new ElasticaDocument($uuid->composeUUID(), $itemDocument);
    }

    /**
     * Filter recursively element for index and data.
     *
     * @param mixed $elements
     *
     * @return mixed $element
     */
    private function filterElementRecursively(array $elements)
    {
        foreach ($elements as $key => $element) {
            if (is_array($element)) {
                $elements[$key] = $this->filterElementRecursively($element);
            }
        }

        $elements = array_filter(
            $elements,
            [$this, 'filterElement']
        );

        return $elements;
    }

    /**
     * Filter element for index and data.
     *
     * @param mixed $element
     *
     * @return mixed $element
     */
    private function filterElement($element)
    {
        return !(
            is_null($element) ||
            (is_array($element) && empty($element))
        );
    }

    /**
     * Filter element for search.
     *
     * @param array $elements
     * @param bool  $asList
     *
     * @return mixed $element
     */
    private function filterSearchableElementRecursively(
        array $elements,
        bool $asList
    ) {
        foreach ($elements as $key => $element) {
            if (is_array($element)) {
                $elements[$key] = $this->filterSearchableElementRecursively($element, true);
            }
        }

        $elements = array_filter(
            $elements,
            [$this, 'filterSearchableElement']
        );

        if ($asList) {
            $elements = array_values($elements);
        }

        return $elements;
    }

    /**
     * Filter element for search.
     *
     * @param mixed $element
     *
     * @return mixed $element
     */
    private function filterSearchableElement($element)
    {
        return !(
            is_null($element) ||
            is_bool($element) ||
            (is_string($element) && empty($element)) ||
            (is_array($element) && empty($element))
        );
    }
}
