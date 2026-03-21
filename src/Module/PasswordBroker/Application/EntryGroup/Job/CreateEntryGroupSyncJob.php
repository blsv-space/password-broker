<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryGroup\Job;

use App\Module\PasswordBroker\Application\EntryGroup\Event\EntryGroupCreatedEvent;
use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use App\Module\PasswordBroker\Domain\EntryGroup\Service\EntryGroupDomainService;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\EntryGroupRepository;
use App\Shared\Application\Job\AbstractReplicableSyncJob;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use InvalidArgumentException;

final class CreateEntryGroupSyncJob extends AbstractReplicableSyncJob
{
    public const string PAYLOAD_KEY_ID = EntryGroupRepository::FIELD_ID;
    public const string PAYLOAD_KEY_NAME = EntryGroupRepository::FIELD_NAME;
    public const string PAYLOAD_KEY_PARENT_ENTRY_GROUP_ID = EntryGroupRepository::FIELD_PARENT_ENTRY_GROUP_ID;

    /**
     * @throws PersistenceException
     */
    #[\Override]
    public function handle(): EntryGroup
    {
        $this->validate();

        $entryGroupDomainService = EntryGroupDomainService::getInstance();
        $entryGroupRepository = EntryGroupRepository::getInstance();
        $entryGroup = $entryGroupRepository->mapArrayToEntity($this->payload);
        $parentEntryGroup = null;
        if (!empty($this->payload[self::PAYLOAD_KEY_PARENT_ENTRY_GROUP_ID])) {
            $parentEntryGroup = $entryGroupRepository->findById(EntryGroupId::fromRaw($this->payload[self::PAYLOAD_KEY_PARENT_ENTRY_GROUP_ID]));
            if (!$parentEntryGroup) {
                throw new InvalidArgumentException('Parent Entry Group not found');
            }
        }
        $entryGroup->materializedPath = $entryGroupDomainService->makeMaterializedPath($entryGroup->id, $parentEntryGroup);

        $entryGroupRepository->save($entryGroup);
        EventDispatcher::getInstance()->dispatch(new EntryGroupCreatedEvent($entryGroup));

        return $entryGroup;
    }

    private function validate(): void
    {
        if (empty($this->payload[self::PAYLOAD_KEY_ID])) {
            throw new InvalidArgumentException('Entry Group id is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_NAME])) {
            throw new InvalidArgumentException('Entry Group name is required');
        }
    }

}
