<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Job;

use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldTotpCreatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldTotpUpdatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Job\ValidateTrait\EntryFieldTotpValidate;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldTotp;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTotpHashAlgorithm;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTotpTimeout;
use Inquisition\Core\Application\Event\EventInterface;
use Override;

/**
 * @extends AbstractUpdateEntryFieldSyncJob<EntryFieldTotp, EntryFieldTotpCreatedEvent>
 */
final class UpdateEntryFieldTotpSyncJob extends AbstractUpdateEntryFieldSyncJob
{
    use EntryFieldTotpValidate;

    #[Override]
    protected function getEvent(AbstractEntryField $entry): EventInterface
    {
        return new EntryFieldTotpUpdatedEvent(
            $entry,
        );
    }

    #[Override]
    protected function updateByEntryFieldType(AbstractEntryField $entry): void
    {
        $entry->totpHashAlgorithm = EntryFieldTotpHashAlgorithm::fromRaw($this->payload[self::PAYLOAD_KEY_TOTP_HASH_ALGORITHM]);
        $entry->totpTimeout = EntryFieldTotpTimeout::fromRaw($this->payload[self::PAYLOAD_KEY_TOTP_TIMEOUT]);
    }
}
