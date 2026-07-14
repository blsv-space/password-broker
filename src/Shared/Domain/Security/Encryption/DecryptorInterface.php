<?php

declare(strict_types=1);

namespace App\Shared\Domain\Security\Encryption;

use Inquisition\Foundation\Singleton\SingletonInterface;

interface DecryptorInterface extends SingletonInterface
{
    public function decrypt(string $cipherText, string $password, string $iv, string $tag): string;
}
