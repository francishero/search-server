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
 * Class AsynchronousFunctionalTest.
 */
abstract class AsynchronousFunctionalTest extends ServiceFunctionalTest
{
    /**
     * Use asynchronous commands.
     *
     * @return bool
     */
    protected static function asynchronousCommands(): bool
    {
        return true;
    }

    /**
     * Save events.
     *
     * @return bool
     */
    protected static function saveEvents(): bool
    {
        return true;
    }

    /**
     * Save asynchronous events.
     *
     * @return bool
     */
    protected static function asynchronousEvents(): bool
    {
        return true;
    }

    /**
     * Time to wait after write command.
     */
    protected static function waitAfterWriteCommand()
    {
        usleep(600000);
    }

    /**
     * Time to wait after write exclusive command.
     */
    protected static function waitAfterExclusiveWriteCommand()
    {
        sleep(3);
    }
}
