<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\EventHandler;

use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldCreatedGeneralEvent;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Job\CreateEntryFieldHistoryLinkSyncJob;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Job\CreateEntryFieldHistoryNoteSyncJob;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Job\CreateEntryFieldHistoryPasswordSyncJob;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Job\CreateEntryFieldHistoryTotpSyncJob;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use Inquisition\Core\Application\Event\EventHandlerInterface;
use Inquisition\Core\Application\Event\EventInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;

/**
 * @implements EventHandlerInterface<EntryFieldCreatedGeneralEvent>
 */
class EntryFieldCreatedGeneralEventHandler implements EventHandlerInterface
{
    /**
     * @param  EntryFieldCreatedGeneralEvent $event
     * @throws PersistenceException
     */
    #[\Override]
    public function handle(EventInterface $event): void
    {
        $job = match ($event->getEntry()->type->toRaw()) {
            EntryFieldTypeEnum::LINK->value => new CreateEntryFieldHistoryLinkSyncJob($event->getEntry()->getAsArray()),
            EntryFieldTypeEnum::NOTE->value => new CreateEntryFieldHistoryNoteSyncJob($event->getEntry()->getAsArray()),
            EntryFieldTypeEnum::PASSWORD->value => new CreateEntryFieldHistoryPasswordSyncJob($event->getEntry()->getAsArray()),
            EntryFieldTypeEnum::TOTP->value => new CreateEntryFieldHistoryTotpSyncJob($event->getEntry()->getAsArray()),
            default => null,
        };

        if (is_null($job)) {
            return;
        }

        $job->handle();
    }

    #[\Override]
    public function getHandledEvents(): array
    {
        return [EntryFieldCreatedGeneralEvent::class];
    }
}
