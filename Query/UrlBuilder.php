<?php

/*
 * This file is part of the SearchBundle for Symfony2.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Mmoreram\SearchBundle\Query;

/**
 * Class UrlBuilder.
 */
class UrlBuilder
{
    /**
     * Add filter into query.
     *
     * @param Query  $query
     * @param string $filterName
     * @param string $value
     *
     * @return array
     */
    public function addFilterValue(
        Query $query,
        string $filterName,
        string $value
    ) : array {
        $urlParameters = $this->generateQueryUrlParameters($query);

        /**
         * Silent pass if the filter is already applied.
         */
        if (
            isset($urlParameters[$filterName]) &&
            in_array($value, $urlParameters[$filterName])
        ) {
            return $urlParameters;
        }

        $urlParameters[$filterName][] = $value;

        return $urlParameters;
    }

    /**
     * Remove filter from query.
     *
     * @param Query  $query
     * @param string $filterName
     * @param string $value
     *
     * @return array
     */
    public function removeFilterValue(
        Query $query,
        string $filterName,
        string $value = null
    ) : array {
        $urlParameters = $this->generateQueryUrlParameters($query);
        if (is_null($value)) {
            unset($urlParameters[$filterName]);

            return $urlParameters;
        }

        /**
         * Silent pass if the filter does not exist.
         */
        if (!isset($urlParameters[$filterName])) {
            return $urlParameters;
        }

        if (($key = array_search($value, $urlParameters[$filterName])) !== false) {
            unset($urlParameters[$filterName][$key]);
        }

        return $urlParameters;
    }

    /**
     * Change price range.
     *
     * @param Query $query
     *
     * @return array
     */
    public function removePriceRangeFilter(Query $query) : array
    {
        $urlParameters = $this->generateQueryUrlParameters($query);
        unset($urlParameters['from']);
        unset($urlParameters['to']);

        return $urlParameters;
    }

    /**
     * Query to url parameters.
     *
     * @param Query $query
     *
     * @return array
     */
    private function generateQueryUrlParameters(Query $query) : array
    {
        $parameters = array_filter(
            array_map(function (Filter $filter) {
                return $filter->getValues();
            }, $query->getFilters())
        );
        unset($parameters['_query']);
        unset($parameters['price_range']);

        $queryString = $query
            ->getFilter('_query')
            ->getValues()[0];

        if (!empty($queryString)) {
            $parameters['q'] = $queryString;
        }

        $priceRangeFilter = $query->getFilter('price_range');
        if ($priceRangeFilter instanceof Filter) {
            $from = $priceRangeFilter->getValues()['from'];
            $to = $priceRangeFilter->getValues()['to'];

            if ($from > PriceRange::FREE) {
                $parameters['from'] = $from;
            }

            if ($to >= PriceRange::FREE) {
                $parameters['to'] = $to;
            }
        }

        return $parameters;
    }
}