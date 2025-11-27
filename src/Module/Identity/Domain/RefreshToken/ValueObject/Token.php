<?php

namespace App\Module\Identity\Domain\RefreshToken\ValueObject;

use Inquisition\Core\Domain\ValueObject\AbstractValueObject;
use InvalidArgumentException;

/**
 * @property string $value
 */
class Token extends AbstractValueObject
{

    /**
     * @return string
     */
    public function toRaw(): string
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
        if (!is_string($data)) {
            throw new InvalidArgumentException('Invalid data type');
        }
    }
}