<?php

declare(strict_types=1);

namespace App\Module\Identity\Domain\User\Service;

use App\Module\Identity\Domain\User\DTO\RsaKeyPair;
use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use Inquisition\Core\Domain\Service\DomainServiceInterface;
use Inquisition\Foundation\Config\Config;
use Inquisition\Foundation\Singleton\SingletonTrait;
use Inquisition\Foundation\Storage\StorageRegistry;
use OpenSSLAsymmetricKey;

class RsaDomainService implements DomainServiceInterface
{
    use SingletonTrait;
    public const int KEY_SIZE = 4096;
    public const string KEY_HASH = 'sha512';

    public const string DIGEST_ALG = 'sha512';
    public const int PADDING = OPENSSL_PKCS1_OAEP_PADDING;

    /**
     * @throws RsaDomainServiceException
     */
    public function generateKeyPair(string $masterPassword): RsaKeyPair
    {
        $config = [
            "digest_alg" => self::KEY_HASH,
            "private_key_bits" => self::KEY_SIZE,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        $res = openssl_pkey_new($config);
        if ($res === false) {

            $msg = '';
            while ($msgE = openssl_error_string()) {
                $msg .= $msgE . PHP_EOL;
            }
            throw new RsaDomainServiceException('Failed to generate RSA key pair: ' . $msg . '');

        }
        openssl_pkey_export($res, $privateKey, $masterPassword);

        $details = openssl_pkey_get_details($res);
        $publicKey = $details['key'];

        return new RsaKeyPair(privateKey: $privateKey, publicKey: $publicKey);
    }

    protected function getStorageUserKeyPath(UserId $userId): string
    {
        $keyPath = Config::getInstance()->getByPath('security.storage_key_path', '');
        $keyPath = rtrim($keyPath, '/ \\');

        return sprintf('%s/%s.key', $keyPath, $userId->toRaw());
    }

    public function storeUserPrivateKeyFromString(UserId $userId, string $privateKey): void
    {
        $storage = StorageRegistry::getInstance()->storage();
        $storageUserKeyPath = $this->getStorageUserKeyPath($userId);
        $storage->writeByPath($storageUserKeyPath, $privateKey);
    }

    /**
     * @throws RsaDomainServiceException
     */
    public function getUserPrivateKeyString(UserId $userId): string
    {
        $storage = StorageRegistry::getInstance()->storage();
        $storageUserKeyPath = $this->getStorageUserKeyPath($userId);
        if (!$storage->fileExists($storageUserKeyPath)) {
            throw new RsaDomainServiceException('User Private key not found');
        }

        return $storage->readByPath($storageUserKeyPath);
    }

    /**
     * @throws RsaDomainServiceException
     */
    public function getUserPrivateKey(UserId $userId, string $masterPassword): OpenSSLAsymmetricKey
    {
        $userPrivateKeyString = $this->getUserPrivateKeyString($userId);

        return $this->getPrivateKeyFromString($userPrivateKeyString, $masterPassword);
    }

    /**
     * @throws RsaDomainServiceException
     */
    public function getPrivateKeyFromString(string $privateKey, string $masterPassword): OpenSSLAsymmetricKey
    {
        $openSSLAsymmetricKey = openssl_pkey_get_private($privateKey, $masterPassword);
        if ($openSSLAsymmetricKey === false) {
            throw new RsaDomainServiceException('Invalid private key or password');
        }

        return $openSSLAsymmetricKey;
    }

    /**
     * @throws RsaDomainServiceException
     */
    public function getUserPublicKey(User $user): OpenSSLAsymmetricKey
    {
        return $this->getPublicKeyFromString($user->publicKey->toRaw());
    }

    /**
     * @throws RsaDomainServiceException
     */
    public function getPublicKeyFromString(string $publicKey): OpenSSLAsymmetricKey
    {
        $openSSLAsymmetricKey = openssl_pkey_get_public($publicKey);
        if ($openSSLAsymmetricKey === false) {
            throw new RsaDomainServiceException('Invalid public key');
        }

        return $openSSLAsymmetricKey;
    }

    /**
     * @throws RsaDomainServiceException
     */
    public function encryptByPublic(string $data, OpenSSLAsymmetricKey $publicKey): string
    {
        $result = openssl_public_encrypt($data, $encrypted, $publicKey, self::PADDING, self::DIGEST_ALG);
        if ($result === false) {
            throw new RsaDomainServiceException('Encryption failed');
        }

        return $encrypted;
    }

    /**
     * @throws RsaDomainServiceException
     */
    public function decryptByPrivate(string $data, OpenSSLAsymmetricKey $privateKey): string
    {
        $result = openssl_private_decrypt($data, $decrypted, $privateKey, self::PADDING, self::DIGEST_ALG);
        if ($result === false) {
            throw new RsaDomainServiceException('Decryption failed');
        }

        return $decrypted;
    }

    /**
     * @throws RsaDomainServiceException
     */
    public function encryptByPrivate(string $data, OpenSSLAsymmetricKey $privateKey): string
    {
        $result = openssl_private_encrypt($data, $encrypted, $privateKey, self::PADDING);
        if ($result === false) {
            throw new RsaDomainServiceException('Encryption failed');
        }

        return $encrypted;
    }

    /**
     * @throws RsaDomainServiceException
     */
    public function decryptByPublic(string $data, OpenSSLAsymmetricKey $publicKey): string
    {
        $result = openssl_public_decrypt($data, $decrypted, $publicKey, self::PADDING);
        if ($result === false) {
            throw new RsaDomainServiceException('Decryption failed');
        }

        return $decrypted;
    }
}
