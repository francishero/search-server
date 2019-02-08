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

use Apisearch\Http\Http;
use Apisearch\Model\IndexUUID;
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class CreateIndexTest.
 */
class CreateIndexTest extends HttpFunctionalTest
{
    /**
     * Test check health with different tokens.
     */
    public function testCreateIndex(): void
    {
        $this->deleteIndex(
            static::$appId,
            self::$index
        );

        $client = $this->createClient();
        $testRoute = $this->get('router')->generate('search_server_api_create_index', [
            'token' => self::$godToken,
            'app_id' => static::$appId,
            'index' => self::$index,
        ]);

        $client->request(
            'put',
            $testRoute,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                Http::INDEX_FIELD => IndexUUID::createById(self::$index)->toArray(),
            ])
        );

        $response = $client->getResponse();
        $this->assertEquals(
            JsonResponse::HTTP_CREATED,
            $response->getStatusCode()
        );

        $anotherClient = $this->createClient();
        $anotherClient->request(
            'put',
            $testRoute,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                Http::INDEX_FIELD => IndexUUID::createById(self::$index)->toArray(),
            ])
        );

        $anotherResponse = $anotherClient->getResponse();
        $this->assertEquals(
            JsonResponse::HTTP_CONFLICT,
            $anotherResponse->getStatusCode()
        );
    }
}
