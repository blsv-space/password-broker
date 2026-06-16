<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\Job;

use App\Module\PasswordBroker\Application\EntryFieldHistory\Event\EntryFieldHistoryEncryptedValueUpdatedEvent;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\AbstractEntryFieldHistory;
use Inquisition\Core\Application\Event\EventInterface;
use Override;

/**
 * @extends AbstractUpdateEntryFieldHistorySyncJob<AbstractEntryFieldHistory, EntryFieldHistoryEncryptedValueUpdatedEvent>
 */
final class UpdateEntryFieldHistoryEncryptedValueSyncJob extends AbstractUpdateEntryFieldHistorySyncJob
{
    #[Override]
    protected function getEvent(AbstractEntryFieldHistory $entryFieldHistory): EventInterface
    {
        return new EntryFieldHistoryEncryptedValueUpdatedEvent(
            entryFieldHistory: $entryFieldHistory,
        );
    }

    #[Override]
    protected function updateByEntryFieldHistoryType(AbstractEntryFieldHistory $entryFieldHistory): void {}


    #[Override]
    protected function validateByEntryFieldType(): void {}
}
