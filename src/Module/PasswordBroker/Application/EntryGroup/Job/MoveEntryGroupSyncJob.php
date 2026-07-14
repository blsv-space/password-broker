<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryGroup\Job;

use App\Module\PasswordBroker\Application\EntryGroup\Event\EntryGroupMovedEvent;
use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use App\Module\PasswordBroker\Domain\EntryGroup\Service\EntryGroupDomainService;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\MaterializedPath;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\Repository\EntryGroupRepository;
use App\Shared\Application\Job\AbstractReplicableSyncJob;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use InvalidArgumentException;

final class MoveEntryGroupSyncJob extends AbstractReplicableSyncJob
{
    public const string PAYLOAD_KEY_ID = EntryGroupRepository::FIELD_ID;
    public const string PAYLOAD_KEY_PARENT_ENTRY_GROUP_ID = EntryGroupRepository::FIELD_PARENT_ENTRY_GROUP_ID;
    public const string PAYLOAD_UPDATED_AT = EntryGroupRepository::FIELD_UPDATED_AT;

    /**
     * @throws PersistenceException
     */
    #[\Override]
    public function handle(): EntryGroup
    {
        $this->validate();

        $entryGroupDomainService = EntryGroupDomainService::getInstance();
        $entryGroupRepository = EntryGroupRepository::getInstance();
        $entryGroupId = EntryGroupId::fromRaw($this->payload[self::PAYLOAD_KEY_ID]);
        /**
         * @var EntryGroup $entryGroup
         */
        $entryGroup = $entryGroupRepository->findById($entryGroupId);
        if (is_null($entryGroup)) {
            throw new InvalidArgumentException('Entry Group not found');
        }
        $allChildren = $entryGroupRepository->findAllChildren($entryGroup);
        $entryGroupOldPath = $entryGroup->materializedPath->toRaw();
        $parentEntryGroup = null;
        if (!is_null($this->payload[self::PAYLOAD_KEY_PARENT_ENTRY_GROUP_ID])) {
            $parentEntryGroup = $entryGroupRepository->findById(
                EntryGroupId::fromRaw($this->payload[self::PAYLOAD_KEY_PARENT_ENTRY_GROUP_ID]),
            );
            if (!$parentEntryGroup) {
                throw new InvalidArgumentException('Parent Entry Group not found');
            }
        }
        $entryGroup->materializedPath = $entryGroupDomainService->makeMaterializedPath($entryGroup->id, $parentEntryGroup);
        $entryGroup->parentEntryGroupId = $parentEntryGroup?->id ?? null;
        $entryGroupNewPath = $entryGroup->materializedPath->toRaw();
        $entryGroupRepository->beginTransaction();
        $entryGroupRepository->save($entryGroup);
        foreach ($allChildren as $entryGroupChild) {
            $entryGroupChild->materializedPath = MaterializedPath::fromRaw(
                str_replace($entryGroupOldPath, $entryGroupNewPath, $entryGroupChild->materializedPath->toRaw()),
            );
            $entryGroupRepository->save($entryGroupChild);
        }
        $entryGroupRepository->commit();

        EventDispatcher::getInstance()->dispatch(new EntryGroupMovedEvent($entryGroup));

        return $entryGroup;
    }

    private function validate(): void
    {
        if (empty($this->payload[self::PAYLOAD_KEY_ID])) {
            throw new InvalidArgumentException('Entry Group id is required');
        }

        if (empty($this->payload[self::PAYLOAD_UPDATED_AT])
            || !is_string($this->payload[self::PAYLOAD_UPDATED_AT])
        ) {
            throw new InvalidArgumentException('UpdatedAt is required');
        }
    }
}
