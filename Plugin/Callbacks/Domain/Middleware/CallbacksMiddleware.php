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

namespace Apisearch\Plugin\Callbacks\Domain\Middleware;

use Apisearch\Http\Http;
use Apisearch\Http\HttpClient;
use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Plugin\Callbacks\Domain\Adapter\HttpCommandAdapters;
use Apisearch\Plugin\Callbacks\Domain\Callbacks;
use Apisearch\Server\Domain\AsynchronousableCommand;
use Apisearch\Server\Domain\Command\AddToken;
use Apisearch\Server\Domain\Command\IndexItems;
use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\Server\Domain\Query\Query;

/**
 * Class CallbacksMiddleware.
 */
class CallbacksMiddleware implements PluginMiddleware
{
    /**
     * @var Callbacks
     *
     * Callbacks
     */
    private $callbacks;

    /**
     * @var HttpClient
     *
     * Http Client
     */
    private $httpClient;

    /**
     * @var HttpCommandAdapters
     *
     * Http command adapters
     */
    private $httpCommandAdapters;

    /**
     * QueryCallbacksMiddleware constructor.
     *
     * @param Callbacks           $callbacks
     * @param HttpClient          $httpClient
     * @param HttpCommandAdapters $httpCommandAdapters
     */
    public function __construct(
        Callbacks $callbacks,
        HttpClient $httpClient,
        HttpCommandAdapters $httpCommandAdapters
    ) {
        $this->callbacks = $callbacks;
        $this->httpClient = $httpClient;
        $this->httpCommandAdapters = $httpCommandAdapters;
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
        $commandNamespace = get_class($command);
        $commandName = str_replace([
            'Apisearch\Server\Domain\Query\\',
            'Apisearch\Server\Domain\Command\\',
        ], '', $commandNamespace);

        $httpCommandAdapter = $this
            ->httpCommandAdapters
            ->getAdapter($commandNamespace);

        if (!is_null($httpCommandAdapter)) {
            $executableCallbacks = $this
                ->callbacks
                ->getCommandCallbacksForMoment(
                    $commandName,
                    'before'
                );

            foreach ($executableCallbacks as $callback) {
                $result = $this
                    ->httpClient
                    ->get(
                        $callback['endpoint'],
                        $callback['method'],
                        array_filter([
                            Http::APP_ID_FIELD => $command->getAppUUID() instanceof AppUUID
                                ? $command
                                    ->getAppUUID()
                                    ->composeUUID()
                                : false,
                            Http::INDEX_FIELD => $command->getIndexUUID() instanceof IndexUUID
                                ? $command
                                    ->getIndexUUID()
                                    ->composeUUID()
                                : false,
                            Http::TOKEN_FIELD => $command
                                ->getToken()
                                ->getTokenUUID()
                                ->composeUUID(),
                        ]),
                        $httpCommandAdapter->buildBodyByCommand(
                            $callback,
                            $command
                        )
                    );

                $command = $httpCommandAdapter->changeCommandAfterCallbackResponse(
                    $callback,
                    $command,
                    $result
                );
            }
        }

        $result = $next($command);

        if ($command instanceof AsynchronousableCommand) {
            return $result;
        }

        if (!is_null($httpCommandAdapter)) {
            $executableCallbacks = $this
                ->callbacks
                ->getCommandCallbacksForMoment(
                    $commandName,
                    'after'
                );

            foreach ($executableCallbacks as $callback) {
                $resultAsArray = $this
                    ->httpClient
                    ->get(
                        $callback['endpoint'],
                        $callback['method'],
                        array_filter([
                            Http::APP_ID_FIELD => $command->getAppUUID() instanceof AppUUID
                                ? $command
                                    ->getAppUUID()
                                    ->composeUUID()
                                : false,
                            Http::INDEX_FIELD => $command->getIndexUUID() instanceof IndexUUID
                                ? $command
                                    ->getIndexUUID()
                                    ->composeUUID()
                                : false,
                            Http::TOKEN_FIELD => $command
                                ->getToken()
                                ->getTokenUUID()
                                ->composeUUID(),
                        ]),
                        $httpCommandAdapter->buildBodyByCommandAndResponse(
                            $callback,
                            $command,
                            $result
                        )
                    );

                $result = $httpCommandAdapter->changeResponseAfterCallbackResponse(
                    $callback,
                    $command,
                    $resultAsArray,
                    $result
                );
            }
        }

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
            Query::class,
            AddToken::class,
            IndexItems::class,
        ];
    }
}
