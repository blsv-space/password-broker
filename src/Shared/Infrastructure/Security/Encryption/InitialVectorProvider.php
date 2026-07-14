<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security\Encryption;

use App\Shared\Domain\Security\Encryption\InitialVectorProviderInterface;
use Inquisition\Foundation\Singleton\SingletonTrait;
use Override;

final class InitialVectorProvider implements InitialVectorProviderInterface
{
    use SingletonTrait;

    #[Override]
    public function getInitialVector(): string
    {
        return openssl_random_pseudo_bytes(openssl_cipher_iv_length(AesEncryptor::ENCRYPTION_METHOD));
    }

}
