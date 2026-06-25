<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryFieldHistory\ValueObject;

use Inquisition\Core\Domain\ValueObject\AbstractValueObject;
use InvalidArgumentException;
use Override;

/**
 * @property bool $value
 */
class EntryFieldHistoryIsDeleted extends AbstractValueObject
{
    #[Override]
    public function toRaw(): bool
    {
        return $this->value;
    }

    #[Override]
    public static function fromRaw(mixed $data): static
    {
        static::validate($data);

        return new static($data);
    }

    #[Override]
    public static function validate(mixed $data): void
    {
        if (!is_bool($data)) {
            throw new InvalidArgumentException('Invalid data type');
        }
    }
}
