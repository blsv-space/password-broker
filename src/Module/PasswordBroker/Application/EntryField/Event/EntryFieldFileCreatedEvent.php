<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Event;

use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldFile;

/**
 * @extends AbstractEntryFieldCreatedEvent<EntryFieldFile>
 */
final readonly class EntryFieldFileCreatedEvent extends AbstractEntryFieldCreatedEvent
{
    #[\Override]
    public function getEventName(): string
    {
        return parent::getEventName() . '.entryFiled.file.created';
    }
}
