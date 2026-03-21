<?php

declare(strict_types=1);

namespace App\Module\Identity\Infrastructure\User\Repository;

use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Repository\UserRepositoryInterface;
use App\Module\Identity\Domain\User\ValueObject\Email;
use App\Module\Identity\Domain\User\ValueObject\HashedPassword;
use App\Module\Identity\Domain\User\ValueObject\IsAdmin;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\Identity\Domain\User\ValueObject\UserName;
use App\Module\Identity\Domain\User\ValueObject\UserPublicKey;
use App\Module\Identity\Infrastructure\Repository\AbstractIdentityRepository;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\UpdatedAt;
use Inquisition\Core\Domain\Entity\EntityInterface;
use Inquisition\Core\Domain\ValueObject\ValueObjectInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use Inquisition\Foundation\Singleton\SingletonTrait;
use InvalidArgumentException;

/**
 * @method User|null  findOneBy(QueryCriteria[] $criteria = [])
 * @method list<User> findAll()
 * @method list<User> findBy(QueryCriteria[] $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null)
 * @method User|null  findById(ValueObjectInterface $id)
 *
 * @extends AbstractIdentityRepository<User>
 * @implements UserRepositoryInterface<User>
 */
class UserRepository extends AbstractIdentityRepository implements UserRepositoryInterface
{
    use SingletonTrait;

    public const string FIELD_ID = 'id';
    public const string FIELD_USER_NAME = 'userName';
    public const string FIELD_HASHED_PASSWORD = 'hashedPassword';
    public const string FIELD_CREATED_AT = 'createdAt';
    public const string FIELD_UPDATED_AT = 'updatedAt';
    public const string FIELD_EMAIL = 'email';
    public const string FIELD_RSA_PUBLIC_KEY = 'publicKey';
    public const string FIELD_IS_ADMIN = 'isAdmin';

    protected const string TABLE_NAME = 'users';
    protected const string ENTITY_CLASS_NAME = User::class;

    private function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws InvalidArgumentException
     * @return User
     */
    #[\Override]
    protected function mapRowToEntity(array $row): EntityInterface
    {
        return new User(
            id: UserId::fromRaw($row[self::FIELD_ID]),
            userName: UserName::fromRaw($row[self::FIELD_USER_NAME]),
            hashedPassword: HashedPassword::fromRaw($row[self::FIELD_HASHED_PASSWORD]),
            isAdmin: IsAdmin::fromRaw($row[self::FIELD_IS_ADMIN] === 1),
            email: Email::fromRaw($row[self::FIELD_EMAIL]),
            publicKey: UserPublicKey::fromRaw($row[self::FIELD_RSA_PUBLIC_KEY]),
            createdAt: CreatedAt::fromRaw($row[self::FIELD_CREATED_AT]),
            updatedAt: !empty($row[self::FIELD_UPDATED_AT]) ? UpdatedAt::fromRaw($row[self::FIELD_UPDATED_AT]) : null,
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
    public function findByUserName(UserName $userName): ?User
    {
        return $this->findOneBy(
            [new QueryCriteria(
                field: self::FIELD_USER_NAME,
                value: $userName->toRaw(),
            )],
        );
    }

    #[\Override]
    public function mapArrayToEntity(array $array): User
    {
        $createdAt = isset($array[UserRepository::FIELD_CREATED_AT])
            ? CreatedAt::fromRaw($array[UserRepository::FIELD_CREATED_AT])
            : null;
        $updateAt = isset($array[UserRepository::FIELD_UPDATED_AT])
            ? UpdatedAt::fromRaw($array[UserRepository::FIELD_UPDATED_AT])
            : null;

        return new User(
            id: UserId::fromRaw($array[UserRepository::FIELD_ID]),
            userName: UserName::fromRaw($array[UserRepository::FIELD_USER_NAME]),
            hashedPassword: HashedPassword::fromRaw($array[UserRepository::FIELD_HASHED_PASSWORD]),
            isAdmin: IsAdmin::fromRaw($array[UserRepository::FIELD_IS_ADMIN]),
            email: Email::fromRaw($array[UserRepository::FIELD_EMAIL]),
            publicKey: UserPublicKey::fromRaw($array[UserRepository::FIELD_RSA_PUBLIC_KEY]),
            createdAt: $createdAt,
            updatedAt: $updateAt,
        );
    }
}
