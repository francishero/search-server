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

namespace Apisearch\Server\Tests\Functional\Domain\Repository;

/**
 * Class CheckIndexTest.
 */
trait CheckIndexTest
{
    /**
     * Test index check.
     */
    public function testIndexCheck()
    {
        $this->assertTrue($this->checkIndex(
            self::$appId,
            self::$index
        ));

        $this->assertFalse($this->checkIndex(
            self::$appId,
            self::$anotherIndex
        ));

        $this->assertTrue($this->checkIndex(
            self::$anotherAppId,
            self::$index
        ));

        $this->assertFalse($this->checkIndex(
            self::$anotherAppId,
            self::$anotherIndex
        ));

        $this->assertFalse($this->checkIndex(
            self::$anotherAppId,
            self::$anotherIndex.','.self::$index
        ));

        $this->createIndex(self::$appId, self::$anotherIndex);

        $this->assertTrue($this->checkIndex(
            self::$appId,
            self::$anotherIndex.','.self::$index
        ));

        $this->deleteIndex(self::$appId, self::$anotherIndex);
    }
}
