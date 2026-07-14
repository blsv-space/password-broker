<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security\Encryption;

trait SetPasswordTrait
{
    private const string METHOD = 'pbkdf2';
    private const string ALGO = 'sha512';
    private const int ITERATIONS = 1_000_000;
    private const int KEY_LENGTH = 32;

    private function buildKey(string $password): string
    {
        $salt = SystemSaltProvider::getInstance()->getSalt();
        return hash_pbkdf2(self::ALGO, $password, $salt, self::ITERATIONS, self::KEY_LENGTH, true);
    }
}
