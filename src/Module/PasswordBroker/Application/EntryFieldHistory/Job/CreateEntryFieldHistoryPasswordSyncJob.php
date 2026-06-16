<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\Job;

use App\Module\PasswordBroker\Application\EntryField\Job\ValidateTrait\EntryFieldPasswordValidate;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Event\EntryFieldHistoryPasswordCreatedEvent;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\AbstractEntryFieldHistory;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\EntryFieldHistoryPassword;
use Inquisition\Core\Application\Event\EventInterface;
use Override;

/**
 * @extends AbstractCreateEntryFieldHistorySyncJob<EntryFieldHistoryPassword, EntryFieldHistoryPasswordCreatedEvent>
 */
final class CreateEntryFieldHistoryPasswordSyncJob extends AbstractCreateEntryFieldHistorySyncJob
{
    use EntryFieldPasswordValidate;
    #[Override]
    protected function getEvent(AbstractEntryFieldHistory $entryFieldHistory): EventInterface
    {
        return new EntryFieldHistoryPasswordCreatedEvent(
            entryFieldHistory: $entryFieldHistory,
        );
    }
}
