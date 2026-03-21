<?php

declare(strict_types=1);

namespace App\Module\Identity\Domain\RefreshToken\ValueObject;

use Inquisition\Core\Domain\ValueObject\AbstractValueObject;
use InvalidArgumentException;

/**
 * @property string $value
 */
class Token extends AbstractValueObject
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
            throw new InvalidArgumentException('Invalid data type');
        }
    }
}
