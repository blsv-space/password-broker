<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryField\ValueObject;

use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use Inquisition\Core\Domain\ValueObject\AbstractValueObject;
use InvalidArgumentException;

/**
 * @property EntryFieldTypeEnum $value
 */
class EntryFieldType extends AbstractValueObject
{
    #[\Override]
    public function toRaw(): string
    {
        return $this->value->value;
    }

    #[\Override]
    public static function fromRaw(mixed $data): static
    {
        if ($data instanceof EntryFieldTypeEnum) {
            return new static($data);
        }

        static::validate($data);

        return new static(EntryFieldTypeEnum::from($data));
    }

    #[\Override]
    public static function validate(mixed $data): void
    {
        if ($data instanceof EntryFieldTypeEnum) {
            return;
        }

        if (!is_string($data)) {
            throw new InvalidArgumentException('Invalid data type');
        }

        if (!EntryFieldTypeEnum::tryFrom($data)) {
            throw new InvalidArgumentException('Invalid field type');
        }
    }
}
