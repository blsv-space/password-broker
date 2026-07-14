<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\Event;

use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\EntryFieldHistoryTotp;

/**
 * @extends AbstractEntryFieldHistoryCreatedEvent<EntryFieldHistoryTotp>
 */
final readonly class EntryFieldHistoryTotpCreatedEvent extends AbstractEntryFieldHistoryCreatedEvent
{
    #[\Override]
    public function getEventName(): string
    {
        return parent::getEventName() . '.entryFiledHistory.totp.created';
    }
}
