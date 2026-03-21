<?php

declare(strict_types=1);

namespace App\Module\Identity\Domain\User\ValueObject;

use App\Module\Identity\Domain\User\Validator\PasswordValidator;
use Inquisition\Core\Domain\Validator\ValueObjectValidatorInterface;
use Inquisition\Core\Domain\ValueObject\AbstractValueObject;

/**
 * @property string $value
 */
class HashedPassword extends AbstractValueObject
{
    #[\Override]
    public function toRaw(): string
    {
        return $this->value;
    }

    #[\Override]
    public static function fromRaw(mixed $data): static
    {
        return new static($data);
    }

    #[\Override]
    public static function validate(mixed $data): void
    {
        static::getValidator()->validate($data);
    }

    #[\Override]
    protected static function getValidator(): ValueObjectValidatorInterface
    {
        return new PasswordValidator();
    }
}
