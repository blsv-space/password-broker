<?php

declare(strict_types=1);

namespace Tests\Shared;

use Closure;
use Inquisition\Core\Application\Event\EventHandlerInterface;
use Inquisition\Core\Application\Event\EventInterface;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;

/**
 * @implements EventHandlerInterface<EventInterface>
 */
final class TestEventHandler implements EventHandlerInterface
{
    public private(set) int $handledEventsCount;
    /**
     * @var EventInterface[]
     */
    public private(set) array $handledEvents;


    public function __construct(
        private readonly array    $eventNames,
        private readonly ?Closure $eventHandler = null,
    ) {
        $this->handledEventsCount = 0;
        $this->handledEvents = [];
        EventDispatcher::getInstance()->registry($this);
    }

    #[\Override]
    public function handle(EventInterface $event): void
    {
        $this->handledEvents = [...$this->handledEvents, $event];
        $this->handledEventsCount = $this->handledEventsCount + 1;

        if (!$this->eventHandler) {
            return;
        }

        ($this->eventHandler)($event);
    }

    #[\Override]
    public function getHandledEvents(): array
    {
        return $this->eventNames;
    }

    public function wasDispatched(): bool
    {
        return $this->handledEventsCount > 0;
    }
}
