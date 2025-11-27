<?php

namespace Tests\Shared;

use Closure;
use Inquisition\Core\Application\Event\EventHandlerInterface;
use Inquisition\Core\Application\Event\EventInterface;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;

final class TestEventHandler
    implements EventHandlerInterface
{
    private(set) int $handledEventsCount;
    /**
     * @var EventInterface[]
     */
    private(set) array $handledEvents;


    public function __construct(
        private readonly array    $eventNames,
        private readonly ?Closure $eventHandler = null,
    )
    {
        $this->handledEventsCount = 0;
        $this->handledEvents = [];
        EventDispatcher::getInstance()->registry($this);
    }

    /**
     * @param EventInterface $event
     * @return void
     */
    public function handle(EventInterface $event): void
    {
        $this->handledEvents = [...$this->handledEvents, $event];
        $this->handledEventsCount = $this->handledEventsCount + 1;

        if (!$this->eventHandler) {
            return;
        }

        ($this->eventHandler)($event);
    }

    /**
     * @return array|string[]
     */
    public function getHandledEvents(): array
    {
        return $this->eventNames;
    }

    /**
     * @return bool
     */
    public function wasDispatched(): bool
    {
        return $this->handledEventsCount > 0;
    }
}