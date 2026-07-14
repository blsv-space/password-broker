<?php

declare(strict_types=1);

namespace App\Module\Identity\Domain\User\ValueObject;

use Inquisition\Core\Domain\ValueObject\AbstractValueObject;
use InvalidArgumentException;

class Email extends AbstractValueObject
{
    #[\Override]
    public function toRaw(): mixed
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
        $parts = explode('@', $data);
        if (count($parts) !== 2
            || empty($parts[0])
            || empty($parts[1])
            || !str_contains($parts[1], '.')
        ) {
            throw new InvalidArgumentException('Invalid email format');
        }
    }
}
