<?php

declare(strict_types=1);

namespace App\Module\Identity\Infrastructure\Security;

use Inquisition\Foundation\Singleton\SingletonInterface;
use Inquisition\Foundation\Singleton\SingletonTrait;

final class PasswordHasher implements SingletonInterface
{
    use SingletonTrait;

    public function hash(string $plain, ?PasswordHashAlgoEnum $algoEnum = null): string
    {
        $algoEnum = $algoEnum ?? PasswordHashAlgoEnum::BCRYPT;

        return password_hash($plain, $algoEnum->getPHPConstant());
    }

    public function verify(string $plain, string $hashed): bool
    {
        return password_verify($plain, $hashed);
    }

    public function hashIsEqual(string $hashA, string $hashB): bool
    {
        return hash_equals($hashA, $hashB);
    }
}
