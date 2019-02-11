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

declare(strict_types = 1);

namespace Apisearch\Plugin\PredictionIO\Domain\Middleware;

use Apisearch\Server\Domain\Command\AddInteraction;
use Apisearch\Server\Domain\Plugin\PluginMiddleware;
use Apisearch\User\Interaction;
use DateTime;

/**
 * Class InteractionMiddleware
 */
class InteractionMiddleware implements PluginMiddleware
{
    /**
     * Execute middleware.
     *
     * @param AddInteraction $command
     * @param callable $next
     *
     * @return mixed
     */
    public function execute(
        $command,
        $next
    )
    {
        $this->putInteraction($command->getInteraction());

        return $next($command);
    }

    /**
     * Put interaction to predictionIO
     *
     * @param Interaction $interaction
     */
    private function putInteraction(Interaction $interaction)
    {
        $this->postContent([
            'event' => 'interact',
            'entityType' => 'user',
            'entityId' => $interaction->getUser()->getId(),
            'targetEntityType' => 'item',
            'targetEntityId' => $interaction->getItemUUID()->composeUUID(),
            'properties' => [
                'weight' => $interaction->getWeight()
            ],
            'eventTime' => (new DateTime)->format(DateTime::ATOM)
        ]);
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
            AddInteraction::class
        ];
    }

    /**
     * Post content to predictionIO
     *
     * Fire and forget
     *
     * @param array $content
     */
    function postContent(array $content)
    {
        $fp = fsockopen("127.0.0.1", 7070, $errno, $errstr, 30);
        $data = json_encode($content);
        $accessKey = '9Yp1Ot2tGmYYpgqUmxTjzPYnC0mOh5WNPADfedR4vtFNYJsZ7qQpfgMf7fU4JEVo';
        $out = "POST /events.json?accessKey=$accessKey HTTP/1.1\r\n";
        $out.= "Host: 127.0.0.1:7070\r\n";
        $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out.= "Content-Length: ".strlen($data)."\r\n";
        $out.= "Connection: Close\r\n\r\n";
        $out.= $data;

        fwrite($fp, $out);
        fclose($fp);
    }
}