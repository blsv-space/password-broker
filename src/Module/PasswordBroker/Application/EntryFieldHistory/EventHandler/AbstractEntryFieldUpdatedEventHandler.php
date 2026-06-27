<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\EventHandler;

use App\Module\PasswordBroker\Application\EntryField\Event\AbstractEntryFieldUpdatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldLinkUpdatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldNoteUpdatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldPasswordUpdatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldTotpUpdatedEvent;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Job\AbstractCreateEntryFieldHistorySyncJob;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Job\CreateEntryFieldHistoryLinkSyncJob;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Job\CreateEntryFieldHistoryNoteSyncJob;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Job\CreateEntryFieldHistoryPasswordSyncJob;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Job\CreateEntryFieldHistoryTotpSyncJob;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\ValueObject\EntryFieldHistoryId;
use Inquisition\Core\Application\Event\EventHandlerInterface;
use Inquisition\Core\Application\Event\EventInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;

/**
 * @implements EventHandlerInterface<AbstractEntryFieldUpdatedEvent>
 */
class AbstractEntryFieldUpdatedEventHandler implements EventHandlerInterface
{
    /**
     * @param  AbstractEntryFieldUpdatedEvent $event
     * @throws PersistenceException
     */
    #[\Override]
    public function handle(EventInterface $event): void
    {
        $payload = $event->getEntry()->getAsArray();
        $payload[AbstractCreateEntryFieldHistorySyncJob::PAYLOAD_KEY_ID] = EntryFieldHistoryId::generate()->toRaw();
        $payload[AbstractCreateEntryFieldHistorySyncJob::PAYLOAD_KEY_ENTRY_FIELD_ID] = $event->getEntry()->id->toRaw();
        $payload[AbstractCreateEntryFieldHistorySyncJob::PAYLOAD_KEY_EVENT_NAME] = $event->getEventName();
        $payload[AbstractCreateEntryFieldHistorySyncJob::PAYLOAD_KEY_IS_DELETED] = !is_null($event->getEntry()->deletedAt);
        $payload[AbstractCreateEntryFieldHistorySyncJob::PAYLOAD_KEY_CREATED_BY] = $event->getExecutorId();
        $payload[AbstractCreateEntryFieldHistorySyncJob::PAYLOAD_KEY_CREATED_AT] = $event->getEntry()->updatedAt->toRaw();
        $job = match ($event->getEntry()->type->toRaw()) {
            EntryFieldTypeEnum::LINK->value => new CreateEntryFieldHistoryLinkSyncJob($payload),
            EntryFieldTypeEnum::NOTE->value => new CreateEntryFieldHistoryNoteSyncJob($payload),
            EntryFieldTypeEnum::PASSWORD->value => new CreateEntryFieldHistoryPasswordSyncJob($payload),
            EntryFieldTypeEnum::TOTP->value => new CreateEntryFieldHistoryTotpSyncJob($payload),
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
        return [
            EntryFieldLinkUpdatedEvent::class,
            EntryFieldNoteUpdatedEvent::class,
            EntryFieldPasswordUpdatedEvent::class,
            EntryFieldTotpUpdatedEvent::class,
        ];
    }
}
