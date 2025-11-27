<?php

namespace App\Shared\Domain\ValueObject;

use App\Shared\Infrastructure\Security\UuidGenerator;
use Inquisition\Core\Domain\ValueObject\AbstractValueObject;
use InvalidArgumentException;
use Random\RandomException;

/**
 * @property int $value
 */
class Id extends AbstractValueObject
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

        if (!new UuidGenerator()->isValid($data)) {
            throw new InvalidArgumentException('Invalid UUID format');
        }
    }

    /**
     * @return $this
     */
    public static function generate(): static
    {
        return static::fromRaw(new UuidGenerator()->generate());
    }
}