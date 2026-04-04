<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security\Encryption;

use App\Shared\Domain\Security\Encryption\EncryptorInterface;
use App\Shared\Domain\Security\Encryption\Exception\EncryptionException;
use App\Shared\Infrastructure\Security\DTO\AesEncryptedData;
use Inquisition\Foundation\Singleton\SingletonTrait;

final class AesEncryptor implements EncryptorInterface
{
    use SingletonTrait;
    use SetPasswordTrait;
    public const string ENCRYPTION_METHOD = 'aes-256-gcm';

    /**
     * @throws EncryptionException
     */
    #[\Override]
    public function encrypt(string $data, string $password, string $iv): AesEncryptedData
    {
        $key = $this->buildKey($password);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $data,
            self::ENCRYPTION_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
        );

        if ($ciphertext === false) {
            throw new EncryptionException('AES Encryption failed.');
        }

        return new AesEncryptedData(
            encryptedData: $ciphertext,
            tag: $tag,
        );
    }

}
