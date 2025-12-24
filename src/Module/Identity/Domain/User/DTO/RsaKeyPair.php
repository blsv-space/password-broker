<?php

namespace App\Module\Identity\Domain\User\DTO;

class RsaKeyPair
{
    public function __construct(
        public string $publicKey,
        public string $privateKey,
    )
    {}
}