<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Event;

use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldFile;

/**
 * @extends AbstractEntryFieldUpdatedEvent<EntryFieldFile>
 */
final readonly class EntryFieldEncryptedValueUpdatedEvent extends AbstractEntryFieldUpdatedEvent
{
    #[\Override]
    public function getEventName(): string
    {
        return parent::getEventName() . '.entryFiled.encryptedValue.updated';
    }
}
