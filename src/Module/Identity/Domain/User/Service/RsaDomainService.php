<?php

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
use phpseclib3\Crypt\Common\PublicKey as PublicKeyAliasCommon;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey;
use phpseclib3\Crypt\RSA\PublicKey;

class RsaDomainService
    implements DomainServiceInterface
{
    public const int KEY_SIZE = 4096;
    public const string KEY_HASH = 'sha512';

    use SingletonTrait;

    /**
     * @param string $masterPassword
     * @return RsaKeyPair
     */
    public function generateKeyPair(string $masterPassword): RsaKeyPair
    {
        $privateKey = RSA::createKey(self::KEY_SIZE);
        /**
         * @var $privateKey PrivateKey
         */
        $privateKey = $privateKey->withHash(self::KEY_HASH);
        /**
         * @var $privateKey PrivateKey
         * @var $publicKey PublicKey
         */
        $privateKey = $privateKey->withPassword($masterPassword);
        $publicKey = $privateKey->getPublicKey();

        return new RsaKeyPair($privateKey, $publicKey);
    }

    /**
     * @param UserId $userId
     * @return string
     */
    protected function getStorageUserKeyPath(UserId $userId): string
    {
        $keyPath = Config::getInstance()->getByPath('security.storage_key_path', '');
        $keyPath = rtrim($keyPath, '/ \\');

        return sprintf('%s/%s.key', $keyPath, $userId->toRaw());
    }


    /**
     * @param UserId $userId
     * @param PrivateKey $privateKey
     * @return void
     */
    public function storeUserPrivateKey(UserId $userId, PrivateKey $privateKey): void
    {
        $storage = StorageRegistry::getInstance()->storage();
        $storageUserKeyPath = $this->getStorageUserKeyPath($userId);
        $storage->writeByPath($storageUserKeyPath, (string)$privateKey);
    }

    /**
     * @param UserId $userId
     * @param string $privateKey
     * @return void
     */
    public function storeUserPrivateKeyFromString(UserId $userId, string $privateKey): void
    {
        $storage = StorageRegistry::getInstance()->storage();
        $storageUserKeyPath = $this->getStorageUserKeyPath($userId);
        $storage->writeByPath($storageUserKeyPath, $privateKey);
    }

    /**
     * @param UserId $userId
     * @return string
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
     * @param UserId $userId
     * @param string $master_password
     * @return PrivateKeyCommon
     * @throws RsaDomainServiceException
     */
    public function getUserPrivateKey(UserId $userId, string $master_password): PrivateKeyCommon
    {
        return PublicKeyLoader::loadPrivateKey($this->getUserPrivateKeyString($userId), $master_password);
    }

    /**
     * @param User $user
     * @return PublicKeyAliasCommon
     */
    public function getUserPublicKey(User $user): PublicKeyAliasCommon
    {
        return PublicKeyLoader::loadPublicKey($user->publicKey->toRaw());
    }
}