<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\Job;

use App\Module\PasswordBroker\Application\EntryField\Job\ValidateTrait\EntryFieldTotpValidate;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Event\EntryFieldHistoryTotpCreatedEvent;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\AbstractEntryFieldHistory;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\EntryFieldHistoryTotp;
use Inquisition\Core\Application\Event\EventInterface;
use Override;

/**
 * @extends AbstractCreateEntryFieldHistorySyncJob<EntryFieldHistoryTotp, EntryFieldHistoryTotpCreatedEvent>
 */
final class CreateEntryFieldHistoryTotpSyncJob extends AbstractCreateEntryFieldHistorySyncJob
{
    use EntryFieldTotpValidate;

    #[Override]
    protected function getEvent(AbstractEntryFieldHistory $entryFieldHistory): EventInterface
    {
        return new EntryFieldHistoryTotpCreatedEvent(
            entryFieldHistory: $entryFieldHistory,
        );
    }
}
