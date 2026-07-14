<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\Event;

use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\AbstractEntryFieldHistory;

/**
 * @extends AbstractEntryFieldHistoryUpdatedEvent<AbstractEntryFieldHistory>
 */
final readonly class EntryFieldHistoryEncryptedValueUpdatedEvent extends AbstractEntryFieldHistoryUpdatedEvent
{
    #[\Override]
    public function getEventName(): string
    {
        return parent::getEventName() . '.entryFiledHistory.encryptedValue.updated';
    }
}
