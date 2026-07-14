<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Job\ValidateTrait;

use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use InvalidArgumentException;
use Override;

trait EntryFieldTotpValidate
{
    public const string PAYLOAD_KEY_TOTP_TIMEOUT = EntryFieldRepository::FIELD_TOTP_TIMEOUT;
    public const string PAYLOAD_KEY_TOTP_HASH_ALGORITHM = EntryFieldRepository::FIELD_TOTP_HASH_ALGORITHM;

    #[Override]
    protected function validateByEntryFieldType(): void
    {
        if (empty($this->payload[self::PAYLOAD_KEY_TOTP_TIMEOUT])
            && !is_int($this->payload[self::PAYLOAD_KEY_TOTP_TIMEOUT])
            && $this->payload[self::PAYLOAD_KEY_TOTP_TIMEOUT] <= 0
        ) {
            throw new InvalidArgumentException('Entry Field TOTP timeout is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_TOTP_HASH_ALGORITHM])) {
            throw new InvalidArgumentException('Entry Field TOTP hash algorithm is required');
        }
    }
}
