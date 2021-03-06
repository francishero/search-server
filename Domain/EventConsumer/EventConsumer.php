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

namespace Apisearch\Server\Domain\EventConsumer;

use Apisearch\Exception\TransportableException;
use Apisearch\Model\AppUUID;
use Apisearch\Model\IndexUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Consumer;
use Apisearch\Server\Domain\Event\DomainEvent;
use Apisearch\Server\Domain\Event\DomainEventWithRepositoryReference;
use Apisearch\Server\Domain\Event\EventPublisher;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class EventConsumer.
 */
class EventConsumer extends Consumer
{
    /**
     * @var EventPublisher
     *
     * Event publisher
     */
    private $eventPublisher;

    /**
     * ConsumerCommand constructor.
     *
     * @param EventPublisher $eventPublisher
     */
    public function __construct(EventPublisher $eventPublisher)
    {
        $this->eventPublisher = $eventPublisher;
    }

    /**
     * Consume domain event.
     *
     * @param OutputInterface $output
     * @param array           $data
     */
    public function consumeDomainEvent(
        OutputInterface $output,
        array $data
    ) {
        $appUUID = empty($data['app_uuid'])
            ? null
            : AppUUID::createFromArray($data['app_uuid']);

        $repositoryReference = empty($data['index_uuid'])
            ? RepositoryReference::create($appUUID)
            : RepositoryReference::create(
                $appUUID,
                IndexUUID::createFromArray($data['index_uuid'])
            );

        $domainEvent = DomainEvent::fromArray($data['event']);
        $domainEventWithRepositoryReference = new DomainEventWithRepositoryReference(
            $repositoryReference,
            $domainEvent,
            ($data['time_cost'] ?? -1)
        );

        $success = true;
        $message = '';
        $from = microtime(true);
        try {
            $this
                ->eventPublisher
                ->publish($domainEventWithRepositoryReference);
        } catch (TransportableException $exception) {
            // Silent pass
            $success = false;
            $message = $exception->getMessage();
        }

        $domainEventClass = str_replace(
            'Apisearch\Server\Domain\Event\\',
            '',
            get_class($domainEvent)
        );

        $this->logOutput(
            $output,
            $domainEventClass,
            $success,
            $message,
            $from
        );
    }
}
