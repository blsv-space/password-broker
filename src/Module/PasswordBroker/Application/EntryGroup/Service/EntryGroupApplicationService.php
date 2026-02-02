<?php

namespace App\Module\PasswordBroker\Application\EntryGroup\Service;

use App\Module\PasswordBroker\Application\EntryGroup\Job\CreateEntryGroupSyncJob;
use App\Module\PasswordBroker\Application\EntryGroup\Job\DeleteEntryGroupSyncJob;
use App\Module\PasswordBroker\Application\EntryGroup\Job\MoveEntryGroupSyncJob;
use App\Module\PasswordBroker\Application\EntryGroup\Job\RenameEntryGroupSyncJob;
use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use App\Module\PasswordBroker\Domain\EntryGroup\Service\EntryGroupDomainService;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use Inquisition\Core\Application\Job\Exception\JobFailedException;
use Inquisition\Core\Application\Job\Exception\JobRetryableException;
use Inquisition\Core\Application\Service\ApplicationServiceInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use Inquisition\Foundation\Singleton\SingletonTrait;
use Throwable;

class EntryGroupApplicationService
    implements ApplicationServiceInterface
{
    private EntryGroupDomainService $entryGroupDomainService;

    use SingletonTrait;

    private function __construct()
    {
        $this->entryGroupDomainService = EntryGroupDomainService::getInstance();
    }

    /**
     * @param string $name
     * @param EntryGroup|null $parentEntryGroup
     * @return EntryGroup
     * @throws Throwable
     */
    public function createEntryGroupSync(
        string      $name,
        ?EntryGroup $parentEntryGroup = null,
    ): EntryGroup
    {

        return new CreateEntryGroupSyncJob([
            CreateEntryGroupSyncJob::PAYLOAD_KEY_ID => EntryGroupId::generate()->toRaw(),
            CreateEntryGroupSyncJob::PAYLOAD_KEY_NAME => $name,
            CreateEntryGroupSyncJob::PAYLOAD_KEY_PARENT_ENTRY_GROUP_ID => $parentEntryGroup?->id->toRaw() ?? null,
        ])->execute();
    }

    /**
     * @param string $uuid
     * @param string $name
     * @return EntryGroup
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
     * @param string $uuid
     * @return EntryGroup
     * @throws PersistenceException
     */
    public function deleteEntryGroupSync(string $uuid): EntryGroup
    {
        return new DeleteEntryGroupSyncJob([
            DeleteEntryGroupSyncJob::PAYLOAD_KEY_ID => $uuid
        ])->handle();
    }

    /**
     * @param string $uuid
     * @param string|null $targetUuid
     * @return EntryGroup
     * @throws PersistenceException
     */
    public function moveEntryGroupSync(string $uuid, ?string $targetUuid = null): EntryGroup
    {
        return new MoveEntryGroupSyncJob([
            MoveEntryGroupSyncJob::PAYLOAD_KEY_ID => $uuid,
            MoveEntryGroupSyncJob::PAYLOAD_KEY_PARENT_ENTRY_GROUP_ID => $targetUuid
        ])->handle();
    }

    /**
     * @param string $uuid
     * @return EntryGroup|null
     * @throws PersistenceException
     */
    public function getEntryGroupByUuid(string $uuid): ?EntryGroup
    {
        return $this->entryGroupDomainService->findById(EntryGroupId::fromRaw($uuid));
    }

    /**
     * @param QueryCriteria[] $criteria
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return EntryGroup[]
     * @throws PersistenceException
     */
    public function getEntryGroupBy(
        array  $criteria,
        ?array $orderBy = null,
        ?int   $limit = null,
        ?int   $offset = null,
    ): array
    {
        return $this->entryGroupDomainService->findBy(
            criteria: $criteria,
            orderBy: $orderBy,
            limit: $limit,
            offset: $offset
        );
    }

    /**
     * @param QueryCriteria[] $criteria
     * @return int
     * @throws PersistenceException
     */
    public function countEntryGroupsBy(array $criteria = []): int
    {
        return $this->entryGroupDomainService->count($criteria);
    }

    /**
     * @return array
     * @throws PersistenceException
     */
    public function getEntryGroupsAsTree(): array
    {
        return $this->entryGroupDomainService->getEntryGroupsAsTree();
    }
}