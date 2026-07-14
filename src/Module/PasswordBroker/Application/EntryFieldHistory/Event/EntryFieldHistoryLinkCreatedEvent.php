<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\Event;

use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\EntryFieldHistoryLink;

/**
 * @extends AbstractEntryFieldHistoryCreatedEvent<EntryFieldHistoryLink>
 */
final readonly class EntryFieldHistoryLinkCreatedEvent extends AbstractEntryFieldHistoryCreatedEvent
{
    #[\Override]
    public function getEventName(): string
    {
        return parent::getEventName() . '.entryFiledHistory.link.created';
    }
}
