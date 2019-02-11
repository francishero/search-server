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

namespace Apisearch\Plugin\PredictionIO;

use Apisearch\Server\ApisearchServerBundle;
use Apisearch\Server\Domain\Plugin\Plugin;
use Mmoreram\BaseBundle\SimpleBaseBundle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class PredictionIOPluginBundle
 */
class PredictionIOPluginBundle extends SimpleBaseBundle implements Plugin
{
    /**
     * Return all bundle dependencies.
     *
     * Values can be a simple bundle namespace or its instance
     *
     * @param KernelInterface $kernel
     *
     * @return array
     */
    public static function getBundleDependencies(KernelInterface $kernel): array
    {
        return [
            SimpleBaseBundle::class,
            ApisearchServerBundle::class,
        ];
    }

    /**
     * get config files.
     *
     * @return array
     */
    public function getConfigFiles(): array
    {
        return [
            'middleware'
        ];
    }

    /**
     * Get plugin name.
     *
     * @return string
     */
    public function getPluginName(): string
    {
        return 'pio';
    }
}