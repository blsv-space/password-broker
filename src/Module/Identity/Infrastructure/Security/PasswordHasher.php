<?php

namespace App\Module\Identity\Infrastructure\Security;

use Inquisition\Foundation\Singleton\SingletonInterface;
use Inquisition\Foundation\Singleton\SingletonTrait;

final class PasswordHasher
    implements SingletonInterface
{
    use SingletonTrait;

    /**
     * @param string $plain
     * @param PasswordHashAlgoEnum|null $algoEnum
     *
     * @return string
     */
    public function hash(string $plain, ?PasswordHashAlgoEnum $algoEnum = null): string
    {
        $algoEnum = $algoEnum ?? PasswordHashAlgoEnum::BCRYPT;

        return password_hash($plain, $algoEnum->getPHPConstant());
    }

    /**
     * @param string $plain
     * @param string $hashed
     *
     * @return bool
     */
    public function verify(string $plain, string $hashed): bool
    {
        return password_verify($plain, $hashed);
    }

    /**
     * @param string $hashA
     * @param string $hashB
     * @return bool
     */
    public function hashIsEqual(string $hashA, string $hashB): bool
    {
        return hash_equals($hashA, $hashB);
    }
}