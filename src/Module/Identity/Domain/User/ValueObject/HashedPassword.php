<?php

namespace App\Module\Identity\Domain\User\ValueObject;

use App\Module\Identity\Domain\User\Validator\PasswordValidator;
use Inquisition\Core\Domain\Validator\ValueObjectValidatorInterface;
use Inquisition\Core\Domain\ValueObject\AbstractValueObject;

/**
 * @property string $value
 */
class HashedPassword extends AbstractValueObject
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
        return new static($data);
    }

    /**
     * @inheritDoc
     */
    public static function validate(mixed $data): void
    {
        static::getValidator()->validate($data);
    }

    /**
     * @inheritDoc
     */
    protected static function getValidator(): ValueObjectValidatorInterface
    {
        return new PasswordValidator();
    }
}