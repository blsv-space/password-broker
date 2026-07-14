<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\EventHandler;

use App\Module\PasswordBroker\Application\EntryField\Event\AbstractEntryFieldCreatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldLinkCreatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldNoteCreatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldPasswordCreatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldTotpCreatedEvent;
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
 * @implements EventHandlerInterface<AbstractEntryFieldCreatedEvent>
 */
class AbstractEntryFieldCreatedEventHandler implements EventHandlerInterface
{
    /**
     * @param  AbstractEntryFieldCreatedEvent $event
     * @throws PersistenceException
     */
    #[\Override]
    public function handle(EventInterface $event): void
    {
        $payload = $event->getEntry()->getAsArray();
        $payload[AbstractCreateEntryFieldHistorySyncJob::PAYLOAD_KEY_ID] = EntryFieldHistoryId::generate()->toRaw();
        $payload[AbstractCreateEntryFieldHistorySyncJob::PAYLOAD_KEY_ENTRY_FIELD_ID] = $event->getEntry()->id->toRaw();
        $payload[AbstractCreateEntryFieldHistorySyncJob::PAYLOAD_KEY_EVENT_NAME] = $event->getEventName();
        $payload[AbstractCreateEntryFieldHistorySyncJob::PAYLOAD_KEY_IS_DELETED] = false;
        $payload[AbstractCreateEntryFieldHistorySyncJob::PAYLOAD_KEY_CREATED_BY] = $event->getExecutorId();
        $payload[AbstractCreateEntryFieldHistorySyncJob::PAYLOAD_KEY_CREATED_AT] = $event->getEntry()->createdAt->toRaw();
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
            EntryFieldLinkCreatedEvent::class,
            EntryFieldNoteCreatedEvent::class,
            EntryFieldPasswordCreatedEvent::class,
            EntryFieldTotpCreatedEvent::class,
        ];
    }
}
