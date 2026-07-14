<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Job;

use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldTotpUpdatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Job\ValidateTrait\EntryFieldTotpValidate;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldTotp;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTotpHashAlgorithm;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTotpTimeout;
use Inquisition\Core\Application\Event\EventInterface;
use Override;

/**
 * @extends AbstractUpdateEntryFieldSyncJob<EntryFieldTotp, EntryFieldTotpUpdatedEvent>
 */
final class UpdateEntryFieldTotpSyncJob extends AbstractUpdateEntryFieldSyncJob
{
    use EntryFieldTotpValidate;

    #[Override]
    protected function getEvent(AbstractEntryField $entryField): EventInterface
    {
        return new EntryFieldTotpUpdatedEvent(
            entryField: $entryField,
            executorId: $this->payload[self::PAYLOAD_EXECUTED_BY],
        );
    }

    #[Override]
    protected function updateByEntryFieldType(AbstractEntryField $entryField): void
    {
        $entryField->totpHashAlgorithm = EntryFieldTotpHashAlgorithm::fromRaw($this->payload[self::PAYLOAD_KEY_TOTP_HASH_ALGORITHM]);
        $entryField->totpTimeout = EntryFieldTotpTimeout::fromRaw($this->payload[self::PAYLOAD_KEY_TOTP_TIMEOUT]);
    }
}
