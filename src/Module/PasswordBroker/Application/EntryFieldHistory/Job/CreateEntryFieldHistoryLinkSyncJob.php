<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\Job;

use App\Module\PasswordBroker\Application\EntryFieldHistory\Event\EntryFieldHistoryLinkCreatedEvent;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\AbstractEntryFieldHistory;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\EntryFieldHistoryLink;
use Inquisition\Core\Application\Event\EventInterface;
use Override;

/**
 * @extends AbstractCreateEntryFieldHistorySyncJob<EntryFieldHistoryLink, EntryFieldHistoryLinkCreatedEvent>
 */
final class CreateEntryFieldHistoryLinkSyncJob extends AbstractCreateEntryFieldHistorySyncJob
{
    #[Override]
    protected function getEvent(AbstractEntryFieldHistory $entryFieldHistory): EventInterface
    {
        return new EntryFieldHistoryLinkCreatedEvent(
            entryFieldHistory: $entryFieldHistory,
        );
    }

    #[Override]
    protected function validateByEntryFieldType(): void {}
}
