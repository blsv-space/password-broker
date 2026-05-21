<?php

declare(strict_types=1);

namespace App\Module\Identity\Infrastructure\User\Repository;

use App\Module\Identity\Domain\RefreshToken\Entity\RefreshToken;
use App\Module\Identity\Domain\RefreshToken\Repository\RefreshTokenRepositoryInterface;
use App\Module\Identity\Domain\RefreshToken\ValueObject\ExpirationAt;
use App\Module\Identity\Domain\RefreshToken\ValueObject\RefreshTokenId;
use App\Module\Identity\Domain\RefreshToken\ValueObject\Token;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\Identity\Infrastructure\Repository\AbstractIdentityRepository;
use App\Shared\Domain\ValueObject\CreatedAt;
use DateTimeImmutable;
use Inquisition\Core\Domain\Entity\EntityInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryOperatorEnum;
use Inquisition\Foundation\Singleton\SingletonTrait;
use InvalidArgumentException;

/**
 * @extends AbstractIdentityRepository<RefreshToken>
 * @implements RefreshTokenRepositoryInterface<RefreshToken>
 */
class RefreshTokenRepository extends AbstractIdentityRepository implements RefreshTokenRepositoryInterface
{
    use SingletonTrait;

    public const string FIELD_ID = 'id';
    public const string FIELD_USER_ID = 'userId';
    public const string FIELD_TOKEN = 'token';
    public const string FIELD_EXPIRATION_AT = 'expirationAt';
    public const string FIELD_CREATED_AT = 'createdAt';

    protected const string TABLE_NAME = 'refreshTokens';
    protected const string ENTITY_CLASS_NAME = RefreshToken::class;


    private function __construct()
    {
        parent::__construct();
    }

    /**
     *
     *
     * @throws InvalidArgumentException
     * @return RefreshToken
     */
    #[\Override]
    protected function mapRowToEntity(array $row): EntityInterface
    {
        return new RefreshToken(
            id: RefreshTokenId::fromRaw($row[self::FIELD_ID]),
            userId: UserId::fromRaw($row[self::FIELD_USER_ID]),
            token: Token::fromRaw($row[self::FIELD_TOKEN]),
            expirationAt: ExpirationAt::fromRaw($row[self::FIELD_EXPIRATION_AT]),
            createdAt: CreatedAt::fromRaw($row[self::FIELD_CREATED_AT]),
        );
    }

    #[\Override]
    protected function mapEntityToRow(EntityInterface $entity): array
    {
        return $entity->getAsArray();
    }

    /**
     * @throws PersistenceException
     */
    #[\Override]
    public function findByUserId(UserId $userId): ?RefreshToken
    {
        $now = new DateTimeImmutable()->format('Y-m-d H:i:s');

        return $this->findOneBy(
            [
                new QueryCriteria(field: self::FIELD_USER_ID, value: $userId->toRaw()),
                new QueryCriteria(field: self::FIELD_EXPIRATION_AT, value: $now, operator: QueryOperatorEnum::GREATER_THAN),
            ],
        );
    }

    /**
     * @throws PersistenceException
     */
    #[\Override]
    public function cleanExpiredTokens(): void
    {
        $now = new DateTimeImmutable()->format('Y-m-d H:i:s');

        $this->removeBy(criteria: [
            new QueryCriteria(field: self::FIELD_EXPIRATION_AT, value: $now, operator: QueryOperatorEnum::LESS_THAN),
        ]);
    }

    /**
     * @param  RefreshToken         $entity
     * @throws PersistenceException
     */
    #[\Override]
    public function insert(EntityInterface $entity): void
    {
        parent::insert($entity);
    }
}
