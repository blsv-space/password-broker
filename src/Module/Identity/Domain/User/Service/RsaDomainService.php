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
use phpseclib3\Crypt\Common\PrivateKey as PrivateKeyCommon;
use phpseclib3\Crypt\Common\PublicKey as PublicKeyCommon;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey;
use phpseclib3\Crypt\RSA\PublicKey;

class RsaDomainService implements DomainServiceInterface
{
    use SingletonTrait;
    public const int KEY_SIZE = 4096;
    public const string KEY_HASH = 'sha512';

    public function generateKeyPair(string $masterPassword): RsaKeyPair
    {
        $privateKey = RSA::createKey(self::KEY_SIZE)
            ->withHash(self::KEY_HASH)
            ->withPassword($masterPassword);
        $publicKey = $privateKey->getPublicKey();

        return new RsaKeyPair(privateKey: (string) $privateKey, publicKey: (string) $publicKey);
    }

    protected function getStorageUserKeyPath(UserId $userId): string
    {
        $keyPath = Config::getInstance()->getByPath('security.storage_key_path', '');
        $keyPath = rtrim($keyPath, '/ \\');

        return sprintf('%s/%s.key', $keyPath, $userId->toRaw());
    }


    public function storeUserPrivateKey(UserId $userId, PrivateKey $privateKey): void
    {
        $storage = StorageRegistry::getInstance()->storage();
        $storageUserKeyPath = $this->getStorageUserKeyPath($userId);
        $storage->writeByPath($storageUserKeyPath, (string) $privateKey);
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
    public function getUserPrivateKey(UserId $userId, string $masterPassword): PrivateKeyCommon
    {
        return PublicKeyLoader::loadPrivateKey($this->getUserPrivateKeyString($userId), $masterPassword);
    }

    public function getPrivateKeyFromString(string $privateKey, string $masterPassword): PrivateKeyCommon
    {
        return PublicKeyLoader::loadPrivateKey($privateKey, $masterPassword);
    }

    public function getUserPublicKey(User $user): PublicKeyCommon
    {
        return PublicKeyLoader::loadPublicKey($user->publicKey->toRaw());
    }

    public function getPublicKeyFromString(string $publicKey): PublicKeyCommon
    {
        return PublicKeyLoader::loadPublicKey($publicKey);

    }
}
