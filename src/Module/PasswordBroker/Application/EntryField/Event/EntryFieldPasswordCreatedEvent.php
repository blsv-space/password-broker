<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Event;

use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldPassword;

/**
 * @extends AbstractEntryFieldCreatedEvent<EntryFieldPassword>
 */
final readonly class EntryFieldPasswordCreatedEvent extends AbstractEntryFieldCreatedEvent
{
    #[\Override]
    public function getEventName(): string
    {
        return parent::getEventName() . '.entryFiled.password.created';
    }
}
