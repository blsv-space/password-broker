<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject;

use App\Module\PasswordBroker\Domain\EntryGroupUser\Enum\RoleEnum;
use Inquisition\Core\Domain\ValueObject\AbstractValueObject;
use InvalidArgumentException;

/**
 * @property RoleEnum $value
 */
class Role extends AbstractValueObject
{
    #[\Override]
    public function toRaw(): string
    {
        return $this->value->value;
    }

    #[\Override]
    public static function fromRaw(mixed $data): static
    {
        if ($data instanceof RoleEnum) {
            return new static($data);
        }

        static::validate($data);

        return new static(RoleEnum::from($data));
    }

    #[\Override]
    public static function validate(mixed $data): void
    {
        if (!RoleEnum::tryFrom($data)) {
            throw new InvalidArgumentException('Invalid role');
        }
    }
}
