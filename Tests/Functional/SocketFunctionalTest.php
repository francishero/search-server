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

/**
 * Class SocketFunctionalTest.
 */
abstract class SocketFunctionalTest extends HttpFunctionalTest
{
    /**
     * @var string
     *
     * App id
     */
    public static $appId = '5666555test';

    /**
     * Get repository name.
     *
     * @return string
     */
    protected static function getRepositoryName(): string
    {
        return 'search_socket';
    }
}
