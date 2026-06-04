<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Job;

use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldPasswordCreatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldPasswordUpdatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Job\ValidateTrait\EntryFieldPasswordValidate;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldPassword;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldLogin;
use Inquisition\Core\Application\Event\EventInterface;
use Override;

/**
 * @extends AbstractUpdateEntryFieldSyncJob<EntryFieldPassword, EntryFieldPasswordCreatedEvent>
 */
final class UpdateEntryFieldPasswordSyncJob extends AbstractUpdateEntryFieldSyncJob
{
    use EntryFieldPasswordValidate;

    #[Override]
    protected function getEvent(AbstractEntryField $entry): EventInterface
    {
        return new EntryFieldPasswordUpdatedEvent($entry);
    }

    #[Override]
    protected function updateByEntryFieldType(AbstractEntryField $entry): void
    {
        $entry->login = EntryFieldLogin::fromRaw($this->payload[self::PAYLOAD_KEY_LOGIN]);
    }
}
