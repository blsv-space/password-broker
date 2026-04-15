<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\EntryGroupUser\Repository;

use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Entity\EntryGroupUser;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Repository\EntryGroupUserRepositoryInterface;
use App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject\EncryptedAesPassword;
use App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject\EntryGroupUserId;
use App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject\Role;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\Repository\EntryGroupRepository;
use App\Module\PasswordBroker\Infrastructure\Repository\AbstractPasswordBrokerRepository;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\UpdatedAt;
use Inquisition\Core\Domain\Entity\EntityInterface;
use Inquisition\Core\Domain\ValueObject\ValueObjectInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use Inquisition\Foundation\Singleton\SingletonTrait;
use Override;

/**
 * @method EntryGroupUser|null findById(ValueObjectInterface $id)
 * @extends AbstractPasswordBrokerRepository<EntryGroupUser>
 */
class EntryGroupUserRepository extends AbstractPasswordBrokerRepository implements EntryGroupUserRepositoryInterface
{
    use SingletonTrait;

    public const string FIELD_ID = 'id';
    public const string FIELD_USER_ID = 'userId';
    public const string FIELD_ENTRY_GROUP_ID = 'entryGroupId';
    public const string FIELD_ENCRYPTED_AES_PASSWORD = 'encryptedAesPassword';
    public const string FIELD_ROLE = 'role';
    public const string FIELD_CREATED_AT = 'createdAt';
    public const string FIELD_UPDATED_AT = 'updatedAt';

    protected const string TABLE_NAME = 'entryGroupUser';
    protected const string ENTITY_CLASS_NAME = EntryGroupUser::class;

    private function __construct()
    {
        parent::__construct();
    }

    #[Override]
    protected function mapRowToEntity(array $row): EntityInterface
    {
        return new EntryGroupUser(
            id: EntryGroupUserId::fromRaw($row[self::FIELD_ID]),
            entryGroupId: EntryGroupId::fromRaw($row[self::FIELD_ENTRY_GROUP_ID]),
            userId: UserId::fromRaw($row[self::FIELD_USER_ID]),
            role: Role::fromRaw($row[self::FIELD_ROLE]),
            encryptedAesPassword: EncryptedAesPassword::fromRaw($row[self::FIELD_ENCRYPTED_AES_PASSWORD]),
            createdAt: CreatedAt::fromRaw($row[self::FIELD_CREATED_AT]),
            updatedAt: !empty($row[self::FIELD_UPDATED_AT]) ? UpdatedAt::fromRaw($row[self::FIELD_UPDATED_AT]) : null,
        );
    }

    #[Override]
    protected function mapEntityToRow(EntityInterface $entity): array
    {
        return $entity->getAsArray();
    }

    #[Override]
    public function mapArrayToEntity(array $array): EntryGroupUser
    {
        $createdAt = isset($array[EntryGroupRepository::FIELD_CREATED_AT])
            ? CreatedAt::fromRaw($array[EntryGroupRepository::FIELD_CREATED_AT])
            : null;

        return new EntryGroupUser(
            id: EntryGroupUserId::fromRaw($array[self::FIELD_ID]),
            entryGroupId: EntryGroupId::fromRaw($array[self::FIELD_ENTRY_GROUP_ID]),
            userId: UserId::fromRaw($array[self::FIELD_USER_ID]),
            role: Role::fromRaw($array[self::FIELD_ROLE]),
            encryptedAesPassword: EncryptedAesPassword::fromRaw($array[self::FIELD_ENCRYPTED_AES_PASSWORD]),
            createdAt: $createdAt,
            updatedAt: !empty($array[self::FIELD_UPDATED_AT]) ? UpdatedAt::fromRaw($array[self::FIELD_UPDATED_AT]) : null,
        );
    }

    /**
     * @throws PersistenceException
     * @return EntryGroupUser[]
     */
    #[Override]
    public function findByUserId(UserId $userId): array
    {
        return $this->findBy([new QueryCriteria(self::FIELD_USER_ID, $userId->toRaw())]);
    }

    /**
     * @throws PersistenceException
     * @return EntryGroupUser[]
     */
    #[Override]
    public function findByEntryGroupId(EntryGroupId $entryGroupId): array
    {
        return $this->findBy([new QueryCriteria(self::FIELD_ENTRY_GROUP_ID, $entryGroupId->toRaw())]);
    }

    /**
     * @throws PersistenceException
     */
    #[Override]
    public function findByUserIdAndEntryGroupId(UserId $userId, EntryGroupId $entryGroupId): ?EntryGroupUser
    {
        return $this->findOneBy([
            new QueryCriteria(self::FIELD_USER_ID, $userId->toRaw()),
            new QueryCriteria(self::FIELD_ENTRY_GROUP_ID, $entryGroupId->toRaw()),
        ]);
    }

    /**
     * @throws PersistenceException
     */
    #[Override]
    public function isUserInEntryGroup(UserId $userId, EntryGroupId $entryGroupId): bool
    {
        return $this->count([
            new QueryCriteria(self::FIELD_USER_ID, $userId->toRaw()),
            new QueryCriteria(self::FIELD_ENTRY_GROUP_ID, $entryGroupId->toRaw()),
        ]) === 1;
    }

}
