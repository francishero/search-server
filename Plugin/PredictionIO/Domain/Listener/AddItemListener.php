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

namespace Apisearch\Plugin\PredictionIO\Domain\Listener;

use Apisearch\Model\ItemUUID;
use Apisearch\Server\Domain\Event\DomainEventWithRepositoryReference;
use Apisearch\Server\Domain\Event\EventSubscriber;
use Apisearch\Server\Domain\Event\ItemsWereIndexed;
use DateTime;

/**
 * Class AddItemListener
 */
class AddItemListener implements EventSubscriber
{
    /**
     * Subscriber should handle event.
     *
     * @param DomainEventWithRepositoryReference $domainEventWithRepositoryReference
     *
     * @return bool
     */
    public function shouldHandleEvent(DomainEventWithRepositoryReference $domainEventWithRepositoryReference): bool
    {
        return $domainEventWithRepositoryReference->getDomainEvent() instanceof ItemsWereIndexed;
    }

    /**
     * Handle event.
     *
     * @param DomainEventWithRepositoryReference $domainEventWithRepositoryReference
     */
    public function handle(DomainEventWithRepositoryReference $domainEventWithRepositoryReference)
    {
        /**
         * @var $domainEvent ItemsWereIndexed
         */
        $domainEvent = $domainEventWithRepositoryReference->getDomainEvent();
        array_walk($domainEvent->getItemsUUID(), function(ItemUUID $itemUUID) {
            $this->addItem($itemUUID);
        });
    }

    /**
     * Add item
     *
     * @param ItemUUID $itemUUID
     */
    private function addItem(ItemUUID $itemUUID)
    {
        $content = [
            'event' => '$set',
            'entityType' => 'item',
            'entityId' => $itemUUID->composeUUID(),
            'properties' => [],
            'eventTime' => (new DateTime)->format(DateTime::ATOM)
        ];



        $opts = array(
            'http' =>
                array(
                    'method'  => 'POST',
                    'header'  => 'Content-type: application/json',
                    'content' => json_encode($content)
                )
        );

        $context = stream_context_create($opts);
        $accessKey = '9Yp1Ot2tGmYYpgqUmxTjzPYnC0mOh5WNPADfedR4vtFNYJsZ7qQpfgMf7fU4JEVo';
        file_get_contents("http://localhost:7070/events.json?accessKey=$accessKey", false, $context);
    }
}