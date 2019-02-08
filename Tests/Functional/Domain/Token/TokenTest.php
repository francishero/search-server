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

namespace Apisearch\Server\Tests\Functional\Domain\Token;

use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Model\Token;
use Apisearch\Model\TokenUUID;
use Apisearch\Query\Query;
use Apisearch\Server\Tests\Functional\HttpFunctionalTest;

/**
 * Class TokenTest.
 */
abstract class TokenTest extends HttpFunctionalTest
{
    /**
     * Test token creation.
     */
    public function testTokenCreation()
    {
        $token = new Token(TokenUUID::createById('12345'), AppUUID::createById(static::$appId));
        $this->addToken($token);
        $this->assertTrue($this->checkIndex(
            null,
            null,
            new Token(TokenUUID::createById('12345'), AppUUID::createById(static::$appId))
        ));

        $this->deleteToken(TokenUUID::createById('12345'));
        $this->assertFalse($this->checkIndex(
            null,
            null,
            new Token(TokenUUID::createById('12345'), AppUUID::createById(static::$appId))
        ));
    }

    /**
     * Test token without index permissions.
     *
     * @expectedException \Apisearch\Exception\InvalidTokenException
     */
    public function testTokenWithoutIndexPermissions()
    {
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(static::$appId),
            [IndexUUID::createById(self::$anotherIndex)]
        );
        $this->addToken($token, static::$appId);

        $this->query(
            Query::createMatchAll(),
            static::$appId,
            self::$index,
            $token
        );
    }

    /**
     * Test token without endpoint permissions.
     *
     * @expectedException \Apisearch\Exception\InvalidTokenException
     * @dataProvider dataTokenWithoutEndpointPermissionsFailing
     */
    public function testTokenWithoutEndpointPermissionsFailing(array $routes)
    {
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(static::$appId)
        );

        $token->setEndpoints($routes);
        $this->addToken($token, static::$appId);

        $this->query(
            Query::createMatchAll(),
            static::$appId,
            self::$index,
            $token
        );
    }

    /**
     * Data for testTokenWithoutEndpointPermissionsFailing.
     *
     * @return []
     */
    public function dataTokenWithoutEndpointPermissionsFailing()
    {
        return [
            [['get~~v1/events']],
            [['post~~v1']],
            [['post~~v1', 'post~~v1/']],
            [['get~~v1/events', 'post~~v1']],
            [['get~~v1/non-existing', 'post~~v1']],
        ];
    }

    /**
     * Test token without endpoint permissions.
     *
     * @dataProvider dataTokenWithoutEndpointPermissionsAccepted
     */
    public function testTokenWithoutEndpointPermissionsAccepted(array $routes)
    {
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(static::$appId)
        );

        $token->setEndpoints($routes);
        $this->addToken($token, static::$appId);

        $this->query(
            Query::createMatchAll(),
            static::$appId,
            self::$index,
            $token
        );
    }

    /**
     * Data for testTokenWithoutEndpointPermissionsAccepted.
     *
     * @return []
     */
    public function dataTokenWithoutEndpointPermissionsAccepted()
    {
        return [
            [[]],
            [['get~~v1']],
            [['get~~/v1']],
            [['get~~v1/']],
            [['get~~/v1/']],
            [['get~~/v1', 'post~~v1/']],
            [['get~~v1', 'get~~/v1']],
            [['get~~v1/items', 'get~~v1', '']],
            [['get~~v1/events', 'get~~v1', 'get~~/v1', '']],
        ];
    }

    /**
     * Test seconds available.
     *
     * @expectedException \Apisearch\Exception\InvalidTokenException
     */
    public function testSecondsAvailableFailing()
    {
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(static::$appId)
        );
        $token->setSecondsValid(1);
        $this->addToken($token, static::$appId);
        sleep(2);
        $this->query(
            Query::createMatchAll(),
            static::$appId,
            self::$index,
            $token
        );
    }

    /**
     * Test seconds available.
     */
    public function testSecondsAvailableAccepted()
    {
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(static::$appId)
        );
        $token->setSecondsValid(2);
        $this->addToken($token, static::$appId);
        sleep(1);
        $this->query(
            Query::createMatchAll(),
            static::$appId,
            self::$index,
            $token
        );
    }

    /**
     * Test different app id.
     *
     * @expectedException \Apisearch\Exception\InvalidTokenException
     */
    public function testInvalidAppId()
    {
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(static::$appId)
        );
        $this->addToken($token, static::$appId);
        $this->query(
            Query::createMatchAll(),
            self::$anotherAppId,
            self::$index,
            $token
        );
    }

    /**
     * Test max hits per query.
     *
     * @expectedException \Apisearch\Exception\InvalidTokenException
     * @expectedExceptionMessage Token 12345 not valid. Max 2 hits allowed
     */
    public function testMaxHitsPerQuery()
    {
        $token = new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(static::$appId)
        );
        $token->setMaxHitsPerQuery(2);
        $this->addToken($token, static::$appId);
        $this->query(
            Query::createMatchAll(),
            static::$appId,
            self::$index,
            $token
        );
    }

    /**
     * Test get tokens.
     */
    public function testGetTokens()
    {
        $tokenUUID = TokenUUID::createById('12345');
        $token = new Token(
            $tokenUUID,
            AppUUID::createById(static::$appId)
        );
        $this->deleteToken(TokenUUID::createById('12345'));
        $this->assertCount(3, $this->getTokens());
        $this->addToken($token);
        $this->assertCount(4, $this->getTokens());
        $this->deleteToken($tokenUUID);
        $this->assertCount(3, $this->getTokens());
        $this->addToken($token);
        $this->addToken($token);
        $this->addToken($token);
        $this->addToken(new Token(
            TokenUUID::createById('56789'),
            AppUUID::createById(static::$appId)
        ));
        $this->addToken(new Token(
            TokenUUID::createById('56789'),
            AppUUID::createById(static::$appId)
        ));
        $this->assertCount(5, $this->getTokens());
    }

    /**
     * Test delete tokens.
     */
    public function testDeleteTokens()
    {
        $this->addToken(new Token(
            TokenUUID::createById('12345'),
            AppUUID::createById(static::$appId)
        ));
        $this->addToken(new Token(
            TokenUUID::createById('67890'),
            AppUUID::createById(static::$appId)
        ));
        $this->addToken(new Token(
            TokenUUID::createById('aaaaa'),
            AppUUID::createById(static::$appId)
        ));
        $this->addToken(new Token(
            TokenUUID::createById('bbbbb'),
            AppUUID::createById(static::$appId)
        ));
        $this->assertCount(8, $this->getTokens());
        $this->deleteTokens();
        $this->assertCount(3, $this->getTokens());
    }
}
