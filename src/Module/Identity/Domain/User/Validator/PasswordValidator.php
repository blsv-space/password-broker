<?php

namespace App\Module\Identity\Domain\User\Validator;

use Inquisition\Core\Domain\Validator\AbstractValueObjectValidator;
use Inquisition\Foundation\Config\Config;

class PasswordValidator extends AbstractValueObjectValidator
{

    /**
     * @inheritDoc
     */
    protected function doValidate(mixed $data): void
    {
        if (!is_string($data)) {
            $this->addError(
                message: 'Invalid data type',
                field: 'password',
            );
        }

        $passwordMinLength = Config::getInstance()->getByPath('security.password_min_length', 8);

        if (strlen($data) < $passwordMinLength) {
            $this->addError(
                message: "Password must be at least $passwordMinLength characters long",
                field: 'password',
            );
        }
    }
}