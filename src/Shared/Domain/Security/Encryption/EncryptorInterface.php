<?php

declare(strict_types=1);

namespace App\Shared\Domain\Security\Encryption;

use App\Shared\Infrastructure\Security\DTO\AesEncryptedData;
use Inquisition\Foundation\Singleton\SingletonInterface;

interface EncryptorInterface extends SingletonInterface
{
    public function encrypt(string $data, string $password, string $iv): AesEncryptedData;

}
