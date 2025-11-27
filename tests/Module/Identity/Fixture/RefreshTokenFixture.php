<?php

namespace Tests\Module\Identity\Fixture;

use App\Module\Identity\Domain\RefreshToken\Entity\RefreshToken;
use App\Module\Identity\Domain\RefreshToken\ValueObject\ExpirationAt;
use App\Module\Identity\Domain\RefreshToken\ValueObject\RefreshTokenId;
use App\Module\Identity\Domain\RefreshToken\ValueObject\Token;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\Identity\Infrastructure\User\Repository\RefreshTokenRepository;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\DateTime;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Shared\AbstractFixture;

class RefreshTokenFixture extends AbstractFixture
{
    public const string USER_ID = RefreshTokenRepository::FIELD_USER_ID;
    public const string TOKEN = RefreshTokenRepository::FIELD_TOKEN;
    public const string EXPIRATION_AT = RefreshTokenRepository::FIELD_EXPIRATION_AT;
    public const string CREATED_AT = RefreshTokenRepository::FIELD_CREATED_AT;
    public const string ID = RefreshTokenRepository::FIELD_ID;

    /**
     * @param array $attributes
     * @param bool $persist
     *
     * @return RefreshToken
     *
     * @throws PersistenceException
     */
    public static function create(array $attributes = [], bool $persist = false): RefreshToken
    {
        $refreshToken = new RefreshToken(
            userId: UserId::fromRaw($attributes[self::USER_ID] ?? UserFixture::getId()),
            token: Token::fromRaw($attributes[self::TOKEN] ?? static::faker()->sha256()),
            expirationAt: ExpirationAt::fromRaw(
                $attributes[self::EXPIRATION_AT]
                ?? static::faker()->dateTimeBetween('+1 hour', '+1 day')
                ->format(DateTime::FORMAT)
            ),
            createdAt: CreatedAt::fromRaw($attributes[self::CREATED_AT]
                ?? static::faker()->dateTime()->format(DateTime::FORMAT)),
            id: RefreshTokenId::fromRaw(static::generateId($attributes[self::ID] ?? null)),
        );

        if ($persist) {
            static::persist($refreshToken);
        }

        return $refreshToken;
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
    public static function createMany(int $count, array $attributes = [], bool $persist = true): array
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
        return RefreshTokenRepository::getTableName();
    }
}