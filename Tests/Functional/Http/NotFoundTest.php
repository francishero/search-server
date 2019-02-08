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

use Apisearch\Server\Tests\Functional\HttpFunctionalTest;

/**
 * Class NotFoundTest.
 */
class NotFoundTest extends HttpFunctionalTest
{
    /**
     * Test not found on some non existing path.
     */
    public function testNotFoundResponse()
    {
        $client = self::createClient();
        $client->request(
            'get',
            '/v2',
            [
                'app_id' => static::$appId,
                'index' => self::$index,
                'token' => self::$godToken,
            ]
        );

        $response = $client->getResponse();
        $this->assertEquals(
            404,
            $response->getStatusCode()
        );
    }
}
