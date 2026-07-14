<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security\Encryption;

use App\Shared\Domain\Security\Encryption\DecryptorInterface;
use App\Shared\Domain\Security\Encryption\Exception\DecryptionException;
use Inquisition\Foundation\Singleton\SingletonTrait;

final class AesDecryptor implements DecryptorInterface
{
    use SingletonTrait;
    use SetPasswordTrait;

    /**
     * @throws DecryptionException
     */
    #[\Override]
    public function decrypt(string $cipherText, string $password, string $iv, string $tag): string
    {
        $key = $this->buildKey($password);

        $decrypt = openssl_decrypt(
            $cipherText,
            AesEncryptor::ENCRYPTION_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
        );

        if ($decrypt === false) {
            throw new DecryptionException('AES Decryption failed.');
        }

        return $decrypt;
    }
}
