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
 * Class RedirectTest.
 */
class RedirectTest extends HttpFunctionalTest
{
    /**
     * Test redirection on some path.
     */
    public function testRedirection()
    {
        $client = self::createClient();
        $client->request(
            'get',
            '/v1/',
            [
                'app_id' => static::$appId,
                'index' => self::$index,
                'token' => self::$godToken,
            ]
        );

        $response = $client->getResponse();
        $this->assertEquals(
            301,
            $response->getStatusCode()
        );

        $this->assertEquals(
            sprintf('http://localhost/v1?app_id=%s&index=%s&token=%s', static::$appId, self::$index, self::$godToken),
            $response->headers->get('location')
        );
    }
}
