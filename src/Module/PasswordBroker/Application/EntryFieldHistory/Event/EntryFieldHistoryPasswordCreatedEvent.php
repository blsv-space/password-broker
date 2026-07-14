<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\Event;

use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\EntryFieldHistoryPassword;

/**
 * @extends AbstractEntryFieldHistoryCreatedEvent<EntryFieldHistoryPassword>
 */
final readonly class EntryFieldHistoryPasswordCreatedEvent extends AbstractEntryFieldHistoryCreatedEvent
{
    #[\Override]
    public function getEventName(): string
    {
        return parent::getEventName() . '.entryFiledHistory.password.created';
    }
}
