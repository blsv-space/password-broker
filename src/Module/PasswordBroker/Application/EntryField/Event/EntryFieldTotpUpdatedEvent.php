<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Event;

use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldTotp;

/**
 * @extends AbstractEntryFieldUpdatedEvent<EntryFieldTotp>
 */
final readonly class EntryFieldTotpUpdatedEvent extends AbstractEntryFieldUpdatedEvent
{
    #[\Override]
    public function getEventName(): string
    {
        return parent::getEventName() . '.entryFiled.totp.updated';
    }
}
