<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Job;

use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldPasswordCreatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Job\ValidateTrait\EntryFieldPasswordValidate;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldPassword;
use Inquisition\Core\Application\Event\EventInterface;
use Override;

/**
 * @extends AbstractCreateEntryFieldSyncJob<EntryFieldPassword, EntryFieldPasswordCreatedEvent>
 */
final class CreateEntryFieldPasswordSyncJob extends AbstractCreateEntryFieldSyncJob
{
    use EntryFieldPasswordValidate;
    #[Override]
    protected function getEvent(AbstractEntryField $entryField): EventInterface
    {
        return new EntryFieldPasswordCreatedEvent(
            entryField: $entryField,
            executorId: $this->payload[self::PAYLOAD_EXECUTED_BY],
        );
    }
}
