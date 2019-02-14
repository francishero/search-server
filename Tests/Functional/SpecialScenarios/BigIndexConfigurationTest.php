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

namespace Apisearch\Server\Tests\Functional;

use Apisearch\Config\Config;
use Apisearch\Config\Synonym;
use Apisearch\Model\Item;
use Apisearch\Model\ItemUUID;
use Apisearch\Query\Query;

/**
 * Class BigIndexConfigurationTest.
 */
class BigIndexConfigurationTest extends CurlFunctionalTest
{
    /**
     * Test configuration with BIG set of data.
     *
     * @group curl
     * @group special
     */
    public function testConfigBigSetOfData()
    {
        /**
         * Generate 10.000 elements.
         */
        $itemsNB = 100000;
        $items = [];
        for ($i = 0; $i < $itemsNB; ++$i) {
            $items[] = Item::create(
                ItemUUID::createByComposedUUID("$i~new-product"),
                [
                    'new_field_1' => 12345,
                    'new_field_2' => 5678,
                    'new_field_3' => 12345,
                    'new_field_4' => 5678,
                ],
                [
                    'new_field_1' => 12345,
                    'new_field_2' => 5678,
                    'new_field_3' => 12345,
                    'new_field_4' => 5678,
                ],
                [
                    'new_field_1' => '12345',
                    'new_field_2' => '5678',
                    'new_field_3' => '12345',
                    'new_field_4' => 'Alfaguarra',
                ],
                [
                    'new_field_1',
                    'new_field_2',
                    'new_field_3',
                    'new_field_4',
                ]
            );

            if (count($items) >= 100) {
                $this->indexItems($items);
                $items = [];
            }
        }

        $this->assertEquals($itemsNB + 5, $this->query(Query::createMatchAll())->getTotalHits());
        $this->configureIndex(Config::createEmpty()->addSynonym(Synonym::createByWords(['Alfaguarra', 'Flipencio'])));
        $this->assertEquals($itemsNB + 1, $this->query(Query::create('Flipencio'))->getTotalHits());
    }
}
