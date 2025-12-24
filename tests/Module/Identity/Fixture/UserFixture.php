<?php

namespace Tests\Module\Identity\Fixture;

use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\ValueObject\Email;
use App\Module\Identity\Domain\User\ValueObject\HashedPassword;
use App\Module\Identity\Domain\User\ValueObject\IsAdmin;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\Identity\Domain\User\ValueObject\UserName;
use App\Module\Identity\Domain\User\ValueObject\UserPublicKey;
use App\Module\Identity\Infrastructure\User\Repository\UserRepository;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\DateTime;
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

    /**
     * @param array $attributes
     * @param bool $persist
     *
     * @return User
     *
     * @throws PersistenceException
     */
    public static function create(array $attributes = [], bool $persist = false): User
    {
        $user = new User(
            id: UserId::fromRaw(static::generateId($attributes[self::ID] ?? UserId::generate()->toRaw())),
            userName: UserName::fromRaw($attributes[self::USER_NAME] ?? static::faker()->userName()),
            hashedPassword: HashedPassword::fromRaw($attributes[self::HASHED_PASSWORD] ?? static::faker()->sha256()),
            isAdmin: IsAdmin::fromRaw($attributes[self::IS_ADMIN] ?? static::faker()->boolean()),
            email: Email::fromRaw($attributes[self::EMAIL] ?? static::faker()->email()),
            publicKey: UserPublicKey::fromRaw($attributes[self::RSA_PUBLIC_KEY] ?? static::faker()->sha256()),
            createdAt: CreatedAt::fromRaw($attributes[self::CREATED_AT]
                ?? static::faker()->dateTime()->format(DateTime::FORMAT)),
            updatedAt: UpdatedAt::fromRaw($attributes[self::UPDATED_AT]
                ?? static::faker()->dateTime()->format(DateTime::FORMAT)),
        );

        if ($persist) {
            static::persist($user);
        }

        return $user;
    }

    /**
     * @param int $count
     * @param array $attributes
     * @param bool $persist
     *
     * @return array
     *
     * @throws PersistenceException
     */
    public static function createMany(int $count, array $attributes = [], bool $persist = false): array
    {
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = static::create($attributes, $persist);
        }

        return $out;
    }

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return UserRepository::getTableName();
    }
}