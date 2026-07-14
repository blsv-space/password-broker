<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Job;

use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldNoteCreatedEvent;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldNote;
use Inquisition\Core\Application\Event\EventInterface;
use Override;

/**
 * @extends AbstractCreateEntryFieldSyncJob<EntryFieldNote, EntryFieldNoteCreatedEvent>
 */
final class CreateEntryFieldNoteSyncJob extends AbstractCreateEntryFieldSyncJob
{
    #[Override]
    protected function getEvent(AbstractEntryField $entryField): EventInterface
    {
        return new EntryFieldNoteCreatedEvent(
            entryField: $entryField,
            executorId: $this->payload[self::PAYLOAD_EXECUTED_BY],
        );
    }

    #[Override]
    protected function validateByEntryFieldType(): void {}
}
