<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Event;

use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldNote;

/**
 * @extends AbstractEntryFieldCreatedEvent<EntryFieldNote>
 */
final readonly class EntryFieldNoteCreatedEvent extends AbstractEntryFieldCreatedEvent
{
    #[\Override]
    public function getEventName(): string
    {
        return parent::getEventName() . '.entryFiled.note.created';
    }
}
