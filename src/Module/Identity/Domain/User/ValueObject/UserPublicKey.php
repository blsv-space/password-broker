<?php

namespace App\Module\Identity\Domain\User\ValueObject;

use Inquisition\Core\Domain\ValueObject\AbstractValueObject;
use InvalidArgumentException;

class UserPublicKey extends AbstractValueObject
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
        if (is_string($data) === false) {
            throw new InvalidArgumentException('Invalid data type');
        }
    }
}