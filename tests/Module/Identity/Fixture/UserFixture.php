<?php

declare(strict_types=1);

namespace Tests\Module\Identity\Fixture;

use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\Identity\Domain\User\Service\RsaDomainService;
use App\Module\Identity\Domain\User\ValueObject\Email;
use App\Module\Identity\Domain\User\ValueObject\HashedPassword;
use App\Module\Identity\Domain\User\ValueObject\IsAdmin;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\Identity\Domain\User\ValueObject\UserName;
use App\Module\Identity\Domain\User\ValueObject\UserPublicKey;
use App\Module\Identity\Infrastructure\User\Repository\UserRepository;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\DateTime;
use App\Shared\Domain\ValueObject\DeletedAt;
use App\Shared\Domain\ValueObject\UpdatedAt;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Shared\AbstractFixture;

class UserFixture extends AbstractFixture
{
    public const string ID = UserRepository::FIELD_ID;
    public const string USER_NAME = UserRepository::FIELD_USER_NAME;
    public const string EMAIL = UserRepository::FIELD_EMAIL;
    public const string IS_ADMIN = UserRepository::FIELD_IS_ADMIN;
    public const string HASHED_PASSWORD = UserRepository::FIELD_HASHED_PASSWORD;
    public const string RSA_PUBLIC_KEY = UserRepository::FIELD_RSA_PUBLIC_KEY;
    public const string CREATED_AT = UserRepository::FIELD_CREATED_AT;
    public const string UPDATED_AT = UserRepository::FIELD_UPDATED_AT;
    public const string DELETED_AT = UserRepository::FIELD_DELETED_AT;
    public const string MASTER_PASSWORD = 'masterPassword';
    public const string DEFAULT_MASTER_PASSWORD = 'password_master';

    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    #[\Override]
    public static function create(array $attributes = [], bool $persist = false): User
    {
        $userId = UserId::fromRaw(static::generateId($attributes[self::ID] ?? UserId::generate()->toRaw()));
        if (array_key_exists(self::RSA_PUBLIC_KEY, $attributes)) {
            $publicKey = UserPublicKey::fromRaw($attributes[self::RSA_PUBLIC_KEY]);
        } else {
            $rsaDomainService = RsaDomainService::getInstance();
            $rsaKeyPair = $rsaDomainService->generateKeyPair($attributes[self::MASTER_PASSWORD] ?? self::DEFAULT_MASTER_PASSWORD);
            $rsaDomainService->storeUserPrivateKeyFromString($userId, $rsaKeyPair->privateKey);
            $publicKey = UserPublicKey::fromRaw($rsaKeyPair->publicKey);
        }
        $user = new User(
            id: $userId,
            userName: UserName::fromRaw($attributes[self::USER_NAME] ?? static::faker()->userName()),
            hashedPassword: HashedPassword::fromRaw($attributes[self::HASHED_PASSWORD] ?? static::faker()->sha256()),
            isAdmin: IsAdmin::fromRaw($attributes[self::IS_ADMIN] ?? static::faker()->boolean()),
            email: Email::fromRaw($attributes[self::EMAIL] ?? static::faker()->email()),
            publicKey: $publicKey,
            createdAt: CreatedAt::fromRaw($attributes[self::CREATED_AT]
                ?? static::faker()->dateTime()->format(DateTime::FORMAT)),
            updatedAt: UpdatedAt::fromRaw($attributes[self::UPDATED_AT]
                ?? static::faker()->dateTime()->format(DateTime::FORMAT)),
            deletedAt: DeletedAt::fromRaw($attributes[self::DELETED_AT]
                ?? static::faker()->dateTime()->format(DateTime::FORMAT)),
        );

        if ($persist) {
            static::persist($user);
        }

        return $user;
    }

    /**
     *
     *
     * @throws PersistenceException
     */
    #[\Override]
    public static function createMany(int $count, array $attributes = [], bool $persist = false): array
    {
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = static::create($attributes, $persist);
        }

        return $out;
    }

    #[\Override]
    public static function getTableName(): string
    {
        return UserRepository::getTableName();
    }
}
