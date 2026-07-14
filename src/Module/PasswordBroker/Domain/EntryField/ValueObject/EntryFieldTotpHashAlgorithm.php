<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryField\ValueObject;

use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTotpHashAlgorithmEnum;
use Inquisition\Core\Domain\ValueObject\AbstractValueObject;
use InvalidArgumentException;

/**
 * @property EntryFieldTotpHashAlgorithmEnum $value
 */
class EntryFieldTotpHashAlgorithm extends AbstractValueObject
{
    #[\Override]
    public function toRaw(): string
    {
        return $this->value->value;
    }

    #[\Override]
    public static function fromRaw(mixed $data): static
    {
        if ($data instanceof EntryFieldTotpHashAlgorithmEnum) {
            return new static($data);
        }

        static::validate($data);

        return new static(EntryFieldTotpHashAlgorithmEnum::from($data));
    }

    #[\Override]
    public static function validate(mixed $data): void
    {
        if ($data instanceof EntryFieldTotpHashAlgorithmEnum) {
            return;
        }

        if (!is_string($data)) {
            throw new InvalidArgumentException('Invalid data type');
        }

        if (!EntryFieldTotpHashAlgorithmEnum::tryFrom($data)) {
            throw new InvalidArgumentException('Invalid field TOTP hash algorithm');
        }
    }
}
