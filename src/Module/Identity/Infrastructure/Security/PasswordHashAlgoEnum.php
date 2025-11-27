<?php

namespace App\Module\Identity\Infrastructure\Security;

enum PasswordHashAlgoEnum: string
{
    case BCRYPT = 'bcrypt';
    case ARGON2I = 'argon2i';
    case ARGON2ID = 'argon2id';

    public static function default(): self
    {
        return self::BCRYPT;
    }

    public function getPHPConstant(): string
    {
        return constant('PASSWORD_' . strtoupper($this->value));
    }
}
