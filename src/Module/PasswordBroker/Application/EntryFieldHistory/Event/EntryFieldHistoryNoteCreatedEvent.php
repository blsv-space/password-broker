<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\Event;

use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\EntryFieldHistoryNote;

/**
 * @extends AbstractEntryFieldHistoryCreatedEvent<EntryFieldHistoryNote>
 */
final readonly class EntryFieldHistoryNoteCreatedEvent extends AbstractEntryFieldHistoryCreatedEvent
{
    #[\Override]
    public function getEventName(): string
    {
        return parent::getEventName() . '.entryFiledHistory.note.created';
    }
}
