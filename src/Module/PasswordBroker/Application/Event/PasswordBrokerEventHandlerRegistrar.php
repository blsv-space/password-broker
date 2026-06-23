<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\Event;

use App\Module\Identity\Application\User\EventHandler\UserCreatedEventHandler;
use App\Module\PasswordBroker\Application\EntryFieldHistory\EventHandler\EntryFieldCreatedGeneralEventHandler;
use App\Shared\Application\Event\EventHandlerRegistrarInterface;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;

class PasswordBrokerEventHandlerRegistrar implements EventHandlerRegistrarInterface
{
    #[\Override]
    public static function register(): void
    {
        $eventDispatcher = EventDispatcher::getInstance();
        $eventDispatcher->registry(new UserCreatedEventHandler());
        $eventDispatcher->registry(new EntryFieldCreatedGeneralEventHandler());
    }
}
