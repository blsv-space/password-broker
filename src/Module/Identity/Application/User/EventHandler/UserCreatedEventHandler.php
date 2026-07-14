<?php

declare(strict_types=1);

namespace App\Module\Identity\Application\User\EventHandler;

use App\Module\Identity\Application\User\Event\UserCreatedEvent;
use Inquisition\Core\Application\Event\EventHandlerInterface;
use Inquisition\Core\Application\Event\EventInterface;

/**
 * @implements EventHandlerInterface<UserCreatedEvent>
 */
class UserCreatedEventHandler implements EventHandlerInterface
{
    /**
     * @param UserCreatedEvent $event
     */
    #[\Override]
    public function handle(EventInterface $event): void {}

    #[\Override]
    public function getHandledEvents(): array
    {
        return [UserCreatedEvent::class];
    }
}
