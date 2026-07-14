<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Job;

use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldPasswordUpdatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Job\ValidateTrait\EntryFieldPasswordValidate;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldPassword;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldLogin;
use Inquisition\Core\Application\Event\EventInterface;
use Override;

/**
 * @extends AbstractUpdateEntryFieldSyncJob<EntryFieldPassword, EntryFieldPasswordUpdatedEvent>
 */
final class UpdateEntryFieldPasswordSyncJob extends AbstractUpdateEntryFieldSyncJob
{
    use EntryFieldPasswordValidate;

    #[Override]
    protected function getEvent(AbstractEntryField $entryField): EventInterface
    {
        return new EntryFieldPasswordUpdatedEvent(
            entryField: $entryField,
            executorId: $this->payload[self::PAYLOAD_EXECUTED_BY],
        );
    }

    #[Override]
    protected function updateByEntryFieldType(AbstractEntryField $entryField): void
    {
        $entryField->login = EntryFieldLogin::fromRaw($this->payload[self::PAYLOAD_KEY_LOGIN]);
    }
}
