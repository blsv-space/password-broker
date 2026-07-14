<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Job\ValidateTrait;

use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use InvalidArgumentException;
use Override;

trait EntryFieldFiledValidate
{
    public const string PAYLOAD_KEY_FILE_NAME = EntryFieldRepository::FIELD_FILE_NAME;
    public const string PAYLOAD_KEY_FILE_SIZE = EntryFieldRepository::FIELD_FILE_SIZE;
    public const string PAYLOAD_KEY_FILE_MIME = EntryFieldRepository::FIELD_FILE_MIME;

    #[Override]
    protected function validateByEntryFieldType(): void
    {
        if (empty($this->payload[self::PAYLOAD_KEY_FILE_NAME])) {
            throw new InvalidArgumentException('Entry Field File Name is required');
        };
        if (empty($this->payload[self::PAYLOAD_KEY_FILE_SIZE])
            && !is_int($this->payload[self::PAYLOAD_KEY_FILE_SIZE])
            && $this->payload[self::PAYLOAD_KEY_FILE_SIZE] <= 0
        ) {
            throw new InvalidArgumentException('Entry Field File Size is required');
        };
        if (empty($this->payload[self::PAYLOAD_KEY_FILE_MIME])) {
            throw new InvalidArgumentException('Entry Field File Name is required');
        };
    }
}
