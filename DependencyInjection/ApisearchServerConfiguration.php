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
 * @author PuntMig Technologies
 */

declare(strict_types=1);

namespace Apisearch\Server\DependencyInjection;

use Mmoreram\BaseBundle\DependencyInjection\BaseConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Class ApisearchServerConfiguration.
 */
class ApisearchServerConfiguration extends BaseConfiguration
{
    /**
     * Configure the root node.
     *
     * @param ArrayNodeDefinition $rootNode Root node
     */
    protected function setupTree(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->scalarNode('middleware_domain_events_service')
                    ->defaultValue('apisearch_server.middleware.queue_events')
                ->end()
                ->arrayNode('config')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('repository')
                        ->addDefaultsIfNotSet()
                            ->children()
                                ->integerNode('shards')
                                    ->isRequired()
                                ->end()
                                ->integerNode('replicas')
                                    ->isRequired()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('event_repository')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->integerNode('shards')
                                    ->isRequired()
                                ->end()
                                ->integerNode('replicas')
                                    ->isRequired()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('cluster')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('host')
                                ->defaultTrue()
                            ->end()
                            ->integerNode('port')
                                ->defaultNull()
                            ->end();
    }
}
