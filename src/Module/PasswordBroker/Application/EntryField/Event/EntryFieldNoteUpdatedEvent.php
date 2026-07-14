<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Event;

use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldNote;

/**
 * @extends AbstractEntryFieldUpdatedEvent<EntryFieldNote>
 */
final readonly class EntryFieldNoteUpdatedEvent extends AbstractEntryFieldUpdatedEvent
{
    #[\Override]
    public function getEventName(): string
    {
        return parent::getEventName() . '.entryFiled.note.updated';
    }
}
