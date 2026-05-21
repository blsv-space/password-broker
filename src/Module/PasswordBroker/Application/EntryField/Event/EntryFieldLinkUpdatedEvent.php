<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Event;

use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldLink;

/**
 * @extends AbstractEntryFieldUpdatedEvent<EntryFieldLink>
 */
final readonly class EntryFieldLinkUpdatedEvent extends AbstractEntryFieldUpdatedEvent
{
    #[\Override]
    public function getEventName(): string
    {
        return parent::getEventName() . '.entryFiled.link.updated';
    }
}
