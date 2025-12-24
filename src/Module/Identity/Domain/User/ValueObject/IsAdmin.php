<?php

namespace App\Module\Identity\Domain\User\ValueObject;

use Inquisition\Core\Domain\ValueObject\AbstractValueObject;
use InvalidArgumentException;

class IsAdmin extends AbstractValueObject
{

    /**
     * @return bool
     */
    public function toRaw(): mixed
    {
        return $this->value;
    }

    /**
     * @param mixed $data
     * @return static
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
        if (!is_bool($data)) {
            throw new InvalidArgumentException('Invalid data type for IsAdmin');
        }
    }
}