<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Job;

use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldTotpCreatedEvent;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldTotp;
use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use Inquisition\Core\Application\Event\EventInterface;
use InvalidArgumentException;
use Override;

/**
 * @extends AbstractCreateEntryFieldSyncJob<EntryFieldTotp, EntryFieldTotpCreatedEvent>
 */
final class CreateEntryFieldTotpSyncJob extends AbstractCreateEntryFieldSyncJob
{
    public const string PAYLOAD_TOTP_TIMEOUT = EntryFieldRepository::FIELD_TOTP_TIMEOUT;
    public const string PAYLOAD_TOTP_HASH_ALGORITHM = EntryFieldRepository::FIELD_TOTP_HASH_ALGORITHM;
    #[Override]
    protected function getEvent(AbstractEntryField $entry): EventInterface
    {
        return new EntryFieldTotpCreatedEvent($entry);
    }

    #[Override]
    protected function validateByFieldType(): void
    {
        if (empty($this->payload[self::PAYLOAD_TOTP_TIMEOUT])
            && !is_int($this->payload[self::PAYLOAD_TOTP_TIMEOUT])
            && $this->payload[self::PAYLOAD_TOTP_TIMEOUT] <= 0
        ) {
            throw new InvalidArgumentException('Entry Field TOTP timeout is required');
        }

        if (empty($this->payload[self::PAYLOAD_TOTP_HASH_ALGORITHM])) {
            throw new InvalidArgumentException('Entry Field TOTP hash algorithm is required');
        }
    }
}
