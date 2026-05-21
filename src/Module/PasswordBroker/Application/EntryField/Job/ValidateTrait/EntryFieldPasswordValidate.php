<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Job\ValidateTrait;

use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use InvalidArgumentException;
use Override;

trait EntryFieldPasswordValidate
{
    public const string PAYLOAD_KEY_LOGIN = EntryFieldRepository::FIELD_LOGIN;

    #[Override]
    protected function validateByEntryFieldType(): void
    {
        if (empty($this->payload[self::PAYLOAD_KEY_LOGIN])) {
            throw new InvalidArgumentException('Entry Field Login is required');
        }
    }
}
