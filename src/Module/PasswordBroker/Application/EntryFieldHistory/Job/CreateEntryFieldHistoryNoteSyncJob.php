<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\Job;

use App\Module\PasswordBroker\Application\EntryFieldHistory\Event\EntryFieldHistoryNoteCreatedEvent;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\AbstractEntryFieldHistory;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\EntryFieldHistoryNote;
use Inquisition\Core\Application\Event\EventInterface;
use Override;

/**
 * @extends AbstractCreateEntryFieldHistorySyncJob<EntryFieldHistoryNote, EntryFieldHistoryNoteCreatedEvent>
 */
final class CreateEntryFieldHistoryNoteSyncJob extends AbstractCreateEntryFieldHistorySyncJob
{
    #[Override]
    protected function getEvent(AbstractEntryFieldHistory $entryFieldHistory): EventInterface
    {
        return new EntryFieldHistoryNoteCreatedEvent(
            entryFieldHistory: $entryFieldHistory,
        );
    }

    #[Override]
    protected function validateByEntryFieldType(): void {}
}
