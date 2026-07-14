<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\EntryGroup\Repository;

use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use App\Module\PasswordBroker\Domain\EntryGroup\Repository\EntryGroupRepositoryInterface;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupName;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\MaterializedPath;
use App\Module\PasswordBroker\Infrastructure\Repository\AbstractPasswordBrokerRepository;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\DeletedAt;
use App\Shared\Domain\ValueObject\UpdatedAt;
use App\Shared\Infrastructure\Repository\RepositorySoftDeleteInterface;
use App\Shared\Infrastructure\Repository\RepositorySoftDeleteTrait;
use Inquisition\Core\Domain\Entity\EntityInterface;
use Inquisition\Core\Domain\ValueObject\ValueObjectInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryOperatorEnum;
use Inquisition\Foundation\Singleton\SingletonTrait;
use InvalidArgumentException;

/**
 * @method EntryGroup|null findById(ValueObjectInterface $id)
 *
 * @extends AbstractPasswordBrokerRepository<EntryGroup>
 * @implements RepositorySoftDeleteInterface<EntryGroup>
 */
class EntryGroupRepository extends AbstractPasswordBrokerRepository implements EntryGroupRepositoryInterface, RepositorySoftDeleteInterface
{
    use SingletonTrait;

    /**
     * @use RepositorySoftDeleteTrait<EntryGroup>
     */
    use RepositorySoftDeleteTrait;

    public const string FIELD_ID = 'id';
    public const string FIELD_PARENT_ENTRY_GROUP_ID = 'parentEntryGroupId';
    public const string FIELD_MATERIALIZED_PATH = 'materializedPath';
    public const string FIELD_NAME = 'name';
    public const string FIELD_CREATED_AT = 'createdAt';
    public const string FIELD_UPDATED_AT = 'updatedAt';
    public const string FIELD_DELETED_AT = 'deletedAt';


    protected const string TABLE_NAME = 'entryGroups';
    protected const string ENTITY_CLASS_NAME = EntryGroup::class;

    private function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws InvalidArgumentException
     * @return EntryGroup
     */
    #[\Override]
    protected function mapRowToEntity(array $row): EntityInterface
    {
        return new EntryGroup(
            id: EntryGroupId::fromRaw($row[self::FIELD_ID]),
            name: EntryGroupName::fromRaw($row[self::FIELD_NAME]),
            materializedPath: MaterializedPath::fromRaw($row[self::FIELD_MATERIALIZED_PATH]),
            parentEntryGroupId: !empty($row[self::FIELD_PARENT_ENTRY_GROUP_ID])
                ? EntryGroupId::fromRaw($row[self::FIELD_PARENT_ENTRY_GROUP_ID])
                : null,
            createdAt: CreatedAt::fromRaw($row[self::FIELD_CREATED_AT]),
            updatedAt: !empty($row[self::FIELD_UPDATED_AT]) ? UpdatedAt::fromRaw($row[self::FIELD_UPDATED_AT]) : null,
            deletedAt: !empty($row[self::FIELD_DELETED_AT]) ? DeletedAt::fromRaw($row[self::FIELD_DELETED_AT]) : null,
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
    public function findEntryGroupByName(EntryGroupName $entryGroupName): ?EntryGroup
    {
        return $this->findOneBy(
            [new QueryCriteria(
                field: self::FIELD_NAME,
                value: $entryGroupName->toRaw(),
            )],
        );
    }

    /**
     * @throws PersistenceException
     * @return EntryGroup[]
     */
    public function findAllChildren(EntryGroup $entryGroup): array
    {
        return $this->findBy(
            [
                new QueryCriteria(
                    field: EntryGroupRepository::FIELD_MATERIALIZED_PATH,
                    value: $entryGroup->materializedPath->toRaw() . '.%',
                    operator: QueryOperatorEnum::LIKE,
                ),
            ],
        );
    }

    #[\Override]
    public function mapArrayToEntity(array $array): EntryGroup
    {
        $createdAt = isset($array[EntryGroupRepository::FIELD_CREATED_AT])
            ? CreatedAt::fromRaw($array[EntryGroupRepository::FIELD_CREATED_AT])
            : null;
        $updateAt = isset($array[EntryGroupRepository::FIELD_UPDATED_AT])
            ? UpdatedAt::fromRaw($array[EntryGroupRepository::FIELD_UPDATED_AT])
            : null;
        $deletedAt = isset($array[EntryGroupRepository::FIELD_DELETED_AT])
            ? DeletedAt::fromRaw($array[EntryGroupRepository::FIELD_DELETED_AT])
            : null;

        return new EntryGroup(
            id: EntryGroupId::fromRaw($array[EntryGroupRepository::FIELD_ID]),
            name: EntryGroupName::fromRaw($array[EntryGroupRepository::FIELD_NAME]),
            materializedPath: MaterializedPath::fromRaw($array[EntryGroupRepository::FIELD_MATERIALIZED_PATH] ?? ''),
            parentEntryGroupId: !empty($array[EntryGroupRepository::FIELD_PARENT_ENTRY_GROUP_ID])
                ? EntryGroupId::fromRaw($array[EntryGroupRepository::FIELD_PARENT_ENTRY_GROUP_ID])
                : null,
            createdAt: $createdAt,
            updatedAt: $updateAt,
            deletedAt: $deletedAt,
        );
    }
}
