<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use App\Shared\Infrastructure\Security\UuidGenerator;
use Inquisition\Core\Domain\ValueObject\AbstractValueObject;
use InvalidArgumentException;

/**
 * @property string $value
 */
class Id extends AbstractValueObject
{
    #[\Override]
    public function toRaw(): string
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
        if (!is_string($data)) {
            throw new InvalidArgumentException('Invalid data type expected string, got ' . gettype($data));
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
