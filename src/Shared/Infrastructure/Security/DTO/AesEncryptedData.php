<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security\DTO;

final readonly class AesEncryptedData
{
    public function __construct(public string $encryptedData, public string $tag) {}
}
