<?php

declare(strict_types=1);

namespace App\Module\Identity\Domain\User\Security;

use App\Module\Identity\Infrastructure\Security\PasswordHashAlgoEnum;

interface PasswordVerifyInterface
{
    public function hash(string $plain, ?PasswordHashAlgoEnum $algoEnum = null): string;

    public function verify(string $plain, string $hashed): bool;

    public function hashIsEqual(string $hashA, string $hashB): bool;
}
