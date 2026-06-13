<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryField\ValueObject;

use Inquisition\Core\Domain\ValueObject\AbstractValueObject;
use InvalidArgumentException;

/**
 * @property int $value
 */
class EntryFieldFileSize extends AbstractValueObject
{
    #[\Override]
    public function toRaw(): int
    {
        return $this->value;
    }

    #[\Override]
    public static function fromRaw(mixed $data): static
    {
        static::validate($data);

        return new static($data);
    }

    #[\Override]
    public static function validate(mixed $data): void
    {
        if (!is_int($data)) {
            throw new InvalidArgumentException('Invalid data type');
        }

        if ($data < 0) {
            throw new InvalidArgumentException('Invalid data: must be positive');
        }
    }
}
