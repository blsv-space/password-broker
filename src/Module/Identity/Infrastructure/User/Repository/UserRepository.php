<?php

namespace App\Module\Identity\Infrastructure\User\Repository;

use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Repository\UserRepositoryInterface;
use App\Module\Identity\Domain\User\ValueObject\HashedPassword;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\Identity\Domain\User\ValueObject\UserName;
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
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method User|null findById(ValueObjectInterface $entity)
 */
class UserRepository extends AbstractIdentityRepository
    implements UserRepositoryInterface
{
    use SingletonTrait;

    public const string FIELD_ID = 'id';
    public const string FIELD_USER_NAME = 'userName';
    public const string FIELD_HASHED_PASSWORD = 'hashedPassword';
    public const string FIELD_CREATED_AT = 'createdAt';
    public const string FIELD_UPDATED_AT = 'updatedAt';

    protected const string TABLE_NAME = 'users';
    protected const string ENTITY_CLASS_NAME = User::class;

    private function __construct() {
        parent::__construct();
    }

    /**
     * @param array $row
     * @return User
     * @throws InvalidArgumentException
     */
    protected function mapRowToEntity(array $row): EntityInterface
    {
        return new User(
            userName: UserName::fromRaw($row[self::FIELD_USER_NAME]),
            hashedPassword: HashedPassword::fromRaw($row[self::FIELD_HASHED_PASSWORD]),
            id: UserId::fromRaw($row[self::FIELD_ID]),
            createdAt: CreatedAt::fromRaw($row[self::FIELD_CREATED_AT]),
            updatedAt: UpdatedAt::fromRaw($row[self::FIELD_UPDATED_AT]),
        );
    }

    /**
     * @param EntityInterface $entity
     * @return array
     */
    protected function mapEntityToRow(EntityInterface $entity): array
    {
        return $entity->getAsArray();
    }

    /**
     * @param UserName $userName
     * @return User|null
     * @throws PersistenceException
     */
    public function findByUserName(UserName $userName): ?User
    {
        return $this->findOneBy(
            [new QueryCriteria(
                field: self::FIELD_USER_NAME,
                value: $userName->toRaw()
            )]);
    }
}