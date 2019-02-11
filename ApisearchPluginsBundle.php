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

namespace Apisearch\Server;

use Apisearch\Plugin;
use Apisearch\Server\DependencyInjection\Env;
use Apisearch\Server\Domain\Plugin\Plugin as PluginInterface;
use Mmoreram\BaseBundle\BaseBundle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class ApisearchPluginsBundle.
 */
class ApisearchPluginsBundle extends BaseBundle
{
    /**
     * Return all bundle dependencies.
     *
     * Values can be a simple bundle namespace or its instance
     *
     * @return array
     */
    public static function getBundleDependencies(KernelInterface $kernel): array
    {
        $pluginsAsString = Env::get('APISEARCH_ENABLED_PLUGINS', '');
        $pluginsAsArray = explode(',', $pluginsAsString);
        $pluginsAsArray = array_map('trim', $pluginsAsArray);
        $pluginsAsArray = self::resolveAliases($pluginsAsArray);

        $pluginsAsArray = array_filter($pluginsAsArray, function (string $pluginNamespace) {
            if (
                empty($pluginNamespace) ||
                !class_exists($pluginNamespace)
            ) {
                return false;
            }

            $reflectionClass = new \ReflectionClass($pluginNamespace);

            return $reflectionClass->implementsInterface(PluginInterface::class);
        });

        return $pluginsAsArray;
    }

    /**
     * Resolve aliases.
     *
     * @param array $bundles
     *
     * @return array
     */
    private static function resolveAliases(array $bundles): array
    {
        $aliases = [
            'callbacks' => Plugin\Callbacks\CallbacksPluginBundle::class,
            'elastica' => Plugin\Elastica\ElasticaPluginBundle::class,
            'elk' => Plugin\ELK\ELKPluginBundle::class,
            'most_relevant_words' => Plugin\MostRelevantWords\MostRelevantWordsPluginBundle::class,
            'newrelic' => Plugin\NewRelic\NewRelicPluginBundle::class,
            'redis_metadata_fields' => Plugin\RedisMetadataFields\RedisMetadataFieldsPluginBundle::class,
            'redis_storage' => Plugin\RedisStorage\RedisStoragePluginBundle::class,
            'redis_queues' => Plugin\RedisQueue\RedisQueuePluginBundle::class,
            'static_tokens' => Plugin\StaticTokens\StaticTokensPluginBundle::class,
            'rabbitmq' => Plugin\RabbitMQ\RabbitMQPluginBundle::class,
            'security' => Plugin\Security\SecurityPluginBundle::class,
            'pio' => Plugin\PredictionIO\PredictionIOPluginBundle::class,
        ];

        $combined = array_combine(
            array_values($bundles),
            array_values($bundles)
        );

        return array_values(
            array_replace(
                $combined,
                array_intersect_key(
                    $aliases,
                    $combined
                )
            )
        );
    }
}
