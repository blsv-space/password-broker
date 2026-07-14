<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Job;

use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldTotpCreatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Job\ValidateTrait\EntryFieldTotpValidate;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldTotp;
use Inquisition\Core\Application\Event\EventInterface;
use Override;

/**
 * @extends AbstractCreateEntryFieldSyncJob<EntryFieldTotp, EntryFieldTotpCreatedEvent>
 */
final class CreateEntryFieldTotpSyncJob extends AbstractCreateEntryFieldSyncJob
{
    use EntryFieldTotpValidate;

    #[Override]
    protected function getEvent(AbstractEntryField $entryField): EventInterface
    {
        return new EntryFieldTotpCreatedEvent(
            entryField: $entryField,
            executorId: $this->payload[self::PAYLOAD_EXECUTED_BY],
        );
    }
}
