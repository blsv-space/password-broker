<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Job;

use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldNoteCreatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldNoteUpdatedEvent;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldNote;
use Inquisition\Core\Application\Event\EventInterface;
use Override;

/**
 * @extends AbstractUpdateEntryFieldSyncJob<EntryFieldNote, EntryFieldNoteCreatedEvent>
 */
final class UpdateEntryFieldNoteSyncJob extends AbstractUpdateEntryFieldSyncJob
{
    #[Override]
    protected function getEvent(AbstractEntryField $entry): EventInterface
    {
        return new EntryFieldNoteUpdatedEvent($entry);
    }

    #[Override]
    protected function validateByEntryFieldType(): void {}

    #[Override]
    protected function updateByEntryFieldType(AbstractEntryField $entry): void {}
}
