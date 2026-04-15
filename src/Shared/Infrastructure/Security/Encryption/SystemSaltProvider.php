<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security\Encryption;

use App\Shared\Domain\Security\Encryption\SaltProviderInterface;
use Inquisition\Foundation\Singleton\SingletonTrait;
use Inquisition\Foundation\Storage\StorageRegistry;

final class SystemSaltProvider implements SaltProviderInterface
{
    public const string SALT_KEY = 'pbkdf2_salt';

    use SingletonTrait;

    #[\Override]
    public function getSalt(): string
    {
        $storage = StorageRegistry::getInstance()->storage();
        if ($storage->fileExists(self::SALT_KEY)) {
            return $storage->readByPath(self::SALT_KEY);
        }
        $string = openssl_random_pseudo_bytes(32);
        $storage->writeByPath(self::SALT_KEY, $string);

        return $string;
    }
}
