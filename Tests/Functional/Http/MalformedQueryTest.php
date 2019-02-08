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

namespace Apisearch\Server\Tests\Functional\Http;

use Apisearch\Server\Tests\Functional\CurlFunctionalTest;

/**
 * Class MalformedQueryTest.
 */
class MalformedQueryTest extends CurlFunctionalTest
{
    /**
     * Test malformed query.
     *
     * @expectedException \Apisearch\Exception\InvalidFormatException
     */
    public function testMalformedQuery()
    {
        self::makeCurl(
            'v1-query',
            static::$appId,
            self::$index,
            null,
            '{"query":{"q":"","aggregations":{"undefined":{"field":"indexed_metadata.anon"}},"size":1000}}'
        );
    }
}
