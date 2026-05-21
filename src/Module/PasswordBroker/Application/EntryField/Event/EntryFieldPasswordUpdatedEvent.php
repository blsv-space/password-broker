<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Event;

use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldPassword;

/**
 * @extends AbstractEntryFieldUpdatedEvent<EntryFieldPassword>
 */
final readonly class EntryFieldPasswordUpdatedEvent extends AbstractEntryFieldUpdatedEvent
{
    #[\Override]
    public function getEventName(): string
    {
        return parent::getEventName() . '.entryFiled.password.updated';
    }
}
