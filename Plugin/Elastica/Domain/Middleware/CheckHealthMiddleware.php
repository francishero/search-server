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

namespace Apisearch\Plugin\Elastica\Domain\Middleware;

use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\Server\Domain\Query\CheckHealth;
use Elastica\Client;

/**
 * Class CheckHealthMiddleware.
 */
class CheckHealthMiddleware implements PluginMiddleware
{
    /**
     * @var Client
     *
     * Elasticsearch Client
     */
    protected $client;

    /**
     * QueryHandler constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Execute middleware.
     *
     * @param mixed    $command
     * @param callable $next
     *
     * @return mixed
     */
    public function execute(
        $command,
        $next
    ) {
        $data = $next($command);
        $elasticsearchStatus = $this
            ->client
            ->getCluster()
            ->getHealth()
            ->getStatus();
        $data['status']['elasticsearch'] = $elasticsearchStatus;
        $data['healthy'] = $data['healthy'] && in_array(strtolower($elasticsearchStatus), [
            'yellow',
            'green',
        ]);

        return $data;
    }

    /**
     * Commands subscribed namespace. Can refer to specific class namespace, any
     * parent class or any interface.
     *
     * By returning an empty array, means coupled to all.
     *
     * @return string[]
     */
    public function getSubscribedCommands(): array
    {
        return [
            CheckHealth::class,
        ];
    }
}
