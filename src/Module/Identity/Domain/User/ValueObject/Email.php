<?php

namespace App\Module\Identity\Domain\User\ValueObject;

use Inquisition\Core\Domain\ValueObject\AbstractValueObject;
use InvalidArgumentException;

class Email extends AbstractValueObject
{

    /**
     * @inheritDoc
     */
    public function toRaw(): mixed
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public static function fromRaw(mixed $data): static
    {
        static::validate($data);
        return new static($data);
    }

    /**
     * @inheritDoc
     */
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