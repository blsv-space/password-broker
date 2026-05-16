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
    protected function getEvent(AbstractEntryField $entry): EventInterface
    {
        return new EntryFieldNoteCreatedEvent($entry);
    }

    #[Override]
    protected function validateByFieldType(): void {}
}
