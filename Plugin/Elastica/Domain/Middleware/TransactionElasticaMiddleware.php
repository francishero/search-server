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

use Apisearch\Repository\Repository;
use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\Server\Domain\WriteCommand;

/**
 * Class TransactionElasticaMiddleware.
 */
class TransactionElasticaMiddleware implements PluginMiddleware
{
    /**
     * @var Repository
     *
     * Repository
     */
    protected $repository;

    /**
     * QueryHandler constructor.
     *
     * @param Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
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
        $result = $next($command);

        $this
            ->repository
            ->flush();

        return $result;
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
            WriteCommand::class,
        ];
    }
}
