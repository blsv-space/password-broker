<?php

namespace App\Module\Identity\Application\User\EventHandler;

use App\Module\Identity\Application\User\Event\UserCreatedEvent;
use Inquisition\Core\Application\Event\EventHandlerInterface;
use Inquisition\Core\Application\Event\EventInterface;

class UserCreatedEventHandler implements EventHandlerInterface
{

    /**
     * @param UserCreatedEvent $event
     * @return void
     */
    public function handle(EventInterface $event): void
    {
    }

    /**
     * @inheritDoc
     */
    public function getHandledEvents(): array
    {
        return [UserCreatedEvent::class];
    }
}