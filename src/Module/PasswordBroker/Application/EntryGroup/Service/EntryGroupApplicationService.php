<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryGroup\Service;

use App\Module\Identity\Application\User\Service\AuthApplicationService;
use App\Module\Identity\Application\User\Service\Exception\AuthException;
use App\Module\PasswordBroker\Application\EntryGroup\Job\CreateEntryGroupSyncJob;
use App\Module\PasswordBroker\Application\EntryGroup\Job\DeleteEntryGroupSyncJob;
use App\Module\PasswordBroker\Application\EntryGroup\Job\MoveEntryGroupSyncJob;
use App\Module\PasswordBroker\Application\EntryGroup\Job\RenameEntryGroupSyncJob;
use App\Module\PasswordBroker\Domain\EntryGroup\DTO\EntryGroupTreeNode;
use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use App\Module\PasswordBroker\Domain\EntryGroup\Service\EntryGroupDomainService;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\Repository\EntryGroupRepository;
use Inquisition\Core\Application\Service\ApplicationServiceInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\AbstractRepository;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryOperatorEnum;
use Inquisition\Foundation\Singleton\SingletonTrait;
use RuntimeException;
use Throwable;

class EntryGroupApplicationService implements ApplicationServiceInterface
{
    use SingletonTrait;
    private EntryGroupDomainService $entryGroupDomainService;
    private EntryGroupRepository $entryGroupRepository;

    private function __construct()
    {
        $this->entryGroupDomainService = EntryGroupDomainService::getInstance();
        $this->entryGroupRepository = EntryGroupRepository::getInstance();
    }

    /**
     * @throws Throwable
     */
    public function createEntryGroupSync(
        string      $name,
        ?EntryGroup $parentEntryGroup = null,
    ): EntryGroup {

        $authUser = AuthApplicationService::getInstance()->authUser();

        if (!$authUser) {
            throw new AuthException('User not authenticated');
        }

        return new CreateEntryGroupSyncJob([
            CreateEntryGroupSyncJob::PAYLOAD_KEY_ID => EntryGroupId::generate()->toRaw(),
            CreateEntryGroupSyncJob::PAYLOAD_KEY_NAME => $name,
            CreateEntryGroupSyncJob::PAYLOAD_KEY_PARENT_ENTRY_GROUP_ID => $parentEntryGroup?->id->toRaw(),
            CreateEntryGroupSyncJob::PAYLOAD_KEY_USER_ID => $authUser->getId()->toRaw(),
        ])->execute();
    }

    /**
     * @throws PersistenceException
     * @throws Throwable
     */
    public function createEntryGroupFromPrimitivesSync(
        string  $name,
        ?string $parentEntryGroupId = null,
    ): EntryGroup {

        return $this->createEntryGroupSync(
            name: $name,
            parentEntryGroup: $parentEntryGroupId
                ? $this->getEntryGroupByUuid($parentEntryGroupId)
                : null,
        );
    }

    /**
     * @throws PersistenceException
     */
    public function renameEntryGroupSync(string $uuid, string $name): EntryGroup
    {
        return new RenameEntryGroupSyncJob([
            RenameEntryGroupSyncJob::PAYLOAD_KEY_ID => $uuid,
            RenameEntryGroupSyncJob::PAYLOAD_KEY_NAME => $name,
        ])->handle();
    }

    /**
     * @throws PersistenceException
     */
    public function deleteEntryGroupSync(string $uuid): EntryGroup
    {
        return new DeleteEntryGroupSyncJob([
            DeleteEntryGroupSyncJob::PAYLOAD_KEY_ID => $uuid,
        ])->handle();
    }

    /**
     * @throws PersistenceException
     */
    public function moveEntryGroupSync(string $uuid, ?string $targetUuid = null): EntryGroup
    {
        return new MoveEntryGroupSyncJob([
            MoveEntryGroupSyncJob::PAYLOAD_KEY_ID => $uuid,
            MoveEntryGroupSyncJob::PAYLOAD_KEY_PARENT_ENTRY_GROUP_ID => $targetUuid,
        ])->handle();
    }

    /**
     * @throws PersistenceException
     */
    public function search(string $query, ?array $orderBy = null, ?int $limit = null): array
    {
        $query = trim($query);
        $queryParts = array_filter(array_map('trim', explode(' ', $query)));
        $query = count($queryParts) > 0
            ? '%' . implode('%', $queryParts) . '%'
            : '';

        return $this->entryGroupRepository->findBy(
            criteria: [
                new QueryCriteria(
                    field: EntryGroupRepository::FIELD_NAME,
                    value: $query,
                    operator: QueryOperatorEnum::LIKE,
                ),
            ],
            orderBy: $orderBy,
            limit: $limit,
        );
    }

    /**
     * @throws PersistenceException
     */
    public function getEntryGroupByUuid(string $uuid): ?EntryGroup
    {
        return $this->entryGroupRepository->findById(EntryGroupId::fromRaw($uuid));
    }

    /**
     * @param  QueryCriteria[]      $criteria
     * @throws PersistenceException
     * @return EntryGroup[]
     */
    public function getEntryGroupBy(
        array  $criteria,
        ?array $orderBy = null,
        ?int   $limit = null,
        ?int   $offset = null,
    ): array {
        return $this->entryGroupRepository->findBy(
            criteria: $criteria,
            orderBy: $orderBy,
            limit: $limit,
            offset: $offset,
        );
    }

    /**
     * @param  QueryCriteria[]      $criteria
     * @throws PersistenceException
     */
    public function countEntryGroupsBy(array $criteria = []): int
    {
        return $this->entryGroupRepository->count($criteria);
    }

    /**
     * @throws AuthException
     * @throws PersistenceException
     * @return EntryGroupTreeNode[]
     */
    public function getEntryGroupsAsTree(): array
    {
        try {
            $user = AuthApplicationService::getInstance()->authUser();
        } catch (Throwable $exception) {
            throw new AuthException('User not authenticated', 0, $exception);
        }

        /**
         * @var EntryGroupTreeNode[] $trees
         */
        $trees = [];
        /**
         * @var EntryGroupTreeNode[] $entryGroupTreeNodes
         */
        $entryGroupTreeNodes = [];
        $entryGroups = $this->entryGroupRepository->findBy(
            criteria: [],
            orderBy: [EntryGroupRepository::FIELD_MATERIALIZED_PATH => AbstractRepository::ORDER_ASC],
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
