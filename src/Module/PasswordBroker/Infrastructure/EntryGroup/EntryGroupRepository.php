<?php

namespace App\Module\PasswordBroker\Infrastructure\EntryGroup;

use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use App\Module\PasswordBroker\Domain\EntryGroup\Repository\EntryGroupRepositoryInterface;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupName;
use App\Module\PasswordBroker\Infrastructure\Repository\AbstractPasswordBrokerRepository;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\UpdatedAt;
use Inquisition\Core\Domain\Entity\EntityInterface;
use Inquisition\Core\Domain\ValueObject\ValueObjectInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use Inquisition\Foundation\Singleton\SingletonTrait;
use InvalidArgumentException;

/**
 * @method EntryGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method EntryGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method EntryGroup|null findById(ValueObjectInterface $entity)
 */
class EntryGroupRepository extends AbstractPasswordBrokerRepository
    implements EntryGroupRepositoryInterface
{
    use SingletonTrait;

    public const string FIELD_ID = 'id';
    public const string FIELD_PARENT_ENTRY_GROUP_ID = 'parentEntryGroupId';
    public const string FIELD_MATERIALIZED_PATH = 'materializedPath';
    public const string FIELD_NAME = 'name';
    public const string FIELD_CREATED_AT = 'createdAt';
    public const string FIELD_UPDATED_AT = 'updatedAt';
    public const string FIELD_DELETED_AT = 'deletedAt';


    protected const string TABLE_NAME = 'entryGroups';
    protected const string ENTITY_CLASS_NAME = EntryGroup::class;

    private function __construct() {
        parent::__construct();
    }

    /**
     * @param array $row
     * @return EntryGroup
     * @throws InvalidArgumentException
     */
    protected function mapRowToEntity(array $row): EntityInterface
    {
        return new EntryGroup(
            id: EntryGroupId::fromRaw($row[self::FIELD_ID]),
            parentEntryGroupId: !empty($row[self::FIELD_PARENT_ENTRY_GROUP_ID]) ?EntryGroupId::fromRaw($row[self::FIELD_PARENT_ENTRY_GROUP_ID]) : null,
            entryGroupName: $row[self::FIELD_NAME],
            materializedPath: $row[self::FIELD_MATERIALIZED_PATH],
            createdAt: CreatedAt::fromRaw($row[self::FIELD_CREATED_AT]),
            updatedAt: !empty($row[self::FIELD_UPDATED_AT]) ? UpdatedAt::fromRaw($row[self::FIELD_UPDATED_AT]) : null,
            deletedAt: !empty($row[self::FIELD_DELETED_AT]) ? UpdatedAt::fromRaw($row[self::FIELD_DELETED_AT]) : null
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
     * @param EntryGroupName $entryGroupName
     * @return EntryGroupName|null
     * @throws PersistenceException
     */
    public function findEntryGroupByName(EntryGroupName $entryGroupName): ?EntryGroup
    {
        return $this->findOneBy(
            [new QueryCriteria(
                field: self::FIELD_NAME,
                value: $entryGroupName->toRaw()
            )]);
    }
}