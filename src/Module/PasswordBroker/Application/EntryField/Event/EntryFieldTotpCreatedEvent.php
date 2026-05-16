<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Event;

use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldTotp;

/**
 * @extends AbstractEntryFieldCreatedEvent<EntryFieldTotp>
 */
final readonly class EntryFieldTotpCreatedEvent extends AbstractEntryFieldCreatedEvent
{
    #[\Override]
    public function getEventName(): string
    {
        return parent::getEventName() . '.entryFiled.totp.created';
    }
}
