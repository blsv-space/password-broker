<?php

namespace App\Module\PasswordBroker\Domain\EntryGroup\Service;

use App\Module\Identity\Domain\User\Entity\User;
use App\Module\PasswordBroker\Domain\EntryGroup\DTO\EntryGroupTreeNode;
use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use App\Module\PasswordBroker\Domain\EntryGroup\Repository\EntryGroupRepositoryInterface;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupName;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\MaterializedPath;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\EntryGroupRepository;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\UpdatedAt;
use Inquisition\Core\Domain\Service\DomainServiceInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryOperatorEnum;
use Inquisition\Foundation\Singleton\SingletonTrait;
use RuntimeException;

final class EntryGroupDomainService
    implements DomainServiceInterface
{
    public const string MATERIALIZED_PATH_SEPARATOR = '.';
    private EntryGroupRepositoryInterface $entryGroupRepository;

    use SingletonTrait;

    private function __construct()
    {
        $this->entryGroupRepository = EntryGroupRepository::getInstance();
    }

    /**
     * @param QueryCriteria[] $criteria
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     * @throws PersistenceException
     */
    public function findBy(
        array  $criteria = [],
        ?array $orderBy = [],
        ?int   $limit = null,
        ?int   $offset = null
    ): array
    {
        return $this->entryGroupRepository->findBy(
            criteria: $criteria,
            orderBy: $orderBy,
            limit: $limit,
            offset: $offset,
        );
    }

    /**
     * @param EntryGroupId $id
     * @return EntryGroup|null
     * @throws PersistenceException
     */
    public function findById(EntryGroupId $id): ?EntryGroup
    {
        return $this->entryGroupRepository->findById($id);
    }

    /**
     * @param QueryCriteria[] $criteria
     * @return int
     * @throws PersistenceException
     */
    public function count(array $criteria = []): int
    {
        return $this->entryGroupRepository->count($criteria);
    }

    /**
     * @param EntryGroupName $entryGroupName
     * @return EntryGroup|null
     * @throws PersistenceException
     */
    public function findEntryGroupByName(EntryGroupName $entryGroupName): ?EntryGroup
    {
        return $this->entryGroupRepository->findEntryGroupByName($entryGroupName);
    }

    /**
     * @param EntryGroup $entryGroup
     * @return void
     * @throws PersistenceException
     */
    public function save(EntryGroup $entryGroup): void
    {
        $this->entryGroupRepository->save($entryGroup);
    }

    /**
     * @param EntryGroup $entryGroup
     * @return void
     */
    public function delete(EntryGroup $entryGroup): void
    {
        $this->entryGroupRepository->removeById($entryGroup);
    }

    /**
     * @param array $array
     * @return EntryGroup
     */
    public function mapArrayToEntity(array $array): EntryGroup
    {
        $createdAt = isset($array[EntryGroupRepository::FIELD_CREATED_AT])
            ? CreatedAt::fromRaw($array[EntryGroupRepository::FIELD_CREATED_AT])
            : null;
        $updateAt = isset($array[EntryGroupRepository::FIELD_UPDATED_AT])
            ? UpdatedAt::fromRaw($array[EntryGroupRepository::FIELD_UPDATED_AT])
            : null;
        $deletedAt = isset($array[EntryGroupRepository::FIELD_DELETED_AT])
            ? UpdatedAt::fromRaw($array[EntryGroupRepository::FIELD_DELETED_AT])
            : null;

        return new EntryGroup(
            id: EntryGroupId::fromRaw($array[EntryGroupRepository::FIELD_ID]),
            parentEntryGroupId: EntryGroupId::fromRaw($array[EntryGroupRepository::FIELD_PARENT_ENTRY_GROUP_ID]),
            name: EntryGroupName::fromRaw($array[EntryGroupRepository::FIELD_NAME]),
            materializedPath: MaterializedPath::fromRaw($array[EntryGroupRepository::FIELD_MATERIALIZED_PATH]),
            createdAt: $createdAt,
            updatedAt: $updateAt,
            deletedAt: $deletedAt,
        );
    }

    /**
     * @param EntryGroupId $entryGroupId
     * @param EntryGroup|null $parentEntryGroup
     * @return MaterializedPath
     */
    public function makeMaterializedPath(EntryGroupId $entryGroupId, ?EntryGroup $parentEntryGroup = null): MaterializedPath
    {
        if ($parentEntryGroup === null) {
            return MaterializedPath::fromRaw($entryGroupId->toRaw());
        }

        return MaterializedPath::fromRaw($parentEntryGroup->materializedPath->toRaw()
            . self::MATERIALIZED_PATH_SEPARATOR . $entryGroupId->toRaw());
    }

    /**
     * @param EntryGroup $entryGroup
     * @return EntryGroup[]
     * @throws PersistenceException
     */
    public function findAllChildren(EntryGroup $entryGroup): array
    {
        return $this->findBy([
                new QueryCriteria(
                    field: EntryGroupRepository::FIELD_PARENT_ENTRY_GROUP_ID,
                    value: $entryGroup->materializedPath->toRaw() . '%',
                    operator: QueryOperatorEnum::LIKE)]
        );
    }

    /**
     * @return EntryGroupRepositoryInterface
     */
    public function getEntryGroupRepository(): EntryGroupRepositoryInterface
    {
        return $this->entryGroupRepository;
    }

    /**
     * Returns all EntryGroups as a tree.
     * @param User $user
     *
     * @return EntryGroupTreeNode[]
     * @throws PersistenceException
     */
    public function getEntryGroupsAsTree(User $user): array
    {
        /**
         * @var EntryGroupTreeNode[] $trees
         */
        $trees = [];
        /**
         * @var EntryGroupTreeNode[] $entryGroupTreeNodes
         */
        $entryGroupTreeNodes = [];
        $entryGroups = $this->findBy(
            orderBy: [EntryGroupRepository::FIELD_MATERIALIZED_PATH],
        );
        foreach ($entryGroups as $entryGroup) {
            $entryGroupTreeNode = new EntryGroupTreeNode(
                entryGroup: $entryGroup,
            );
            $entryGroupTreeNodes[$entryGroup->id->value] = $entryGroupTreeNode;
            if ($entryGroup->parentEntryGroupId === null) {
                $trees[$entryGroup->id->value] = $entryGroupTreeNode;
                continue;
            }
            if (!array_key_exists($entryGroup->parentEntryGroupId->value, $entryGroupTreeNodes)) {
                throw new RuntimeException('EntryGroup Tree is not valid.');
            }

            $entryGroupTreeNodes[$entryGroup->parentEntryGroupId->value]->children[] = $entryGroupTreeNode;
        }

        return $trees;
    }
}