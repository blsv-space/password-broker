<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Entry\Repository;

use App\Module\PasswordBroker\Domain\Entry\Entity\Entry;
use App\Module\PasswordBroker\Domain\Entry\Repository\EntryRepositoryInterface;
use App\Module\PasswordBroker\Domain\Entry\ValueObject\EntryId;
use App\Module\PasswordBroker\Domain\Entry\ValueObject\Title;
use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
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
use Inquisition\Foundation\Singleton\SingletonTrait;
use InvalidArgumentException;

/**
 * @method Entry|null findById(ValueObjectInterface $id)
 *
 * @extends AbstractPasswordBrokerRepository<Entry>
 * @implements RepositorySoftDeleteInterface<Entry>
 */
class EntryRepository extends AbstractPasswordBrokerRepository implements EntryRepositoryInterface, RepositorySoftDeleteInterface
{
    use SingletonTrait;

    /**
     * @use RepositorySoftDeleteTrait<Entry>
     */
    use RepositorySoftDeleteTrait;

    public const string FIELD_ID = 'id';
    public const string FIELD_ENTRY_GROUP_ID = 'entryGroupId';
    public const string FIELD_TITLE = 'title';
    public const string FIELD_CREATED_AT = 'createdAt';
    public const string FIELD_UPDATED_AT = 'updatedAt';
    public const string FIELD_DELETED_AT = 'deletedAt';


    protected const string TABLE_NAME = 'entries';
    protected const string ENTITY_CLASS_NAME = Entry::class;

    private function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws InvalidArgumentException
     * @return Entry
     */
    #[\Override]
    protected function mapRowToEntity(array $row): EntityInterface
    {
        return new Entry(
            id: EntryId::fromRaw($row[self::FIELD_ID]),
            entryGroupId: EntryGroupId::fromRaw($row[self::FIELD_ENTRY_GROUP_ID]),
            title: Title::fromRaw($row[self::FIELD_TITLE]),
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
     * @param Title $title
     * @return Entry|null
     * @throws PersistenceException
     */
    #[\Override]
    public function findEntryByTitle(Title $title): ?Entry
    {
        return $this->findOneBy(
            [new QueryCriteria(
                field: self::FIELD_TITLE,
                value: $title->toRaw(),
            )],
        );
    }

    #[\Override]
    public function mapArrayToEntity(array $array): Entry
    {
        $createdAt = isset($array[EntryRepository::FIELD_CREATED_AT])
            ? CreatedAt::fromRaw($array[EntryRepository::FIELD_CREATED_AT])
            : null;
        $updateAt = isset($array[EntryRepository::FIELD_UPDATED_AT])
            ? UpdatedAt::fromRaw($array[EntryRepository::FIELD_UPDATED_AT])
            : null;
        $deletedAt = isset($array[EntryRepository::FIELD_DELETED_AT])
            ? DeletedAt::fromRaw($array[EntryRepository::FIELD_DELETED_AT])
            : null;

        return new Entry(
            id: EntryId::fromRaw($array[EntryRepository::FIELD_ID]),
            entryGroupId: EntryGroupId::fromRaw($array[EntryRepository::FIELD_ENTRY_GROUP_ID]),
            title: Title::fromRaw($array[EntryRepository::FIELD_TITLE]),
            createdAt: $createdAt,
            updatedAt: $updateAt,
            deletedAt: $deletedAt,
        );
    }
}
