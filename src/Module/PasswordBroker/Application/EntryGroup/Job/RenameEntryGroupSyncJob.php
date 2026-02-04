<?php

namespace App\Module\PasswordBroker\Application\EntryGroup\Job;

use App\Module\PasswordBroker\Application\EntryGroup\Event\EntryGroupRenamedEvent;
use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use App\Module\PasswordBroker\Domain\EntryGroup\Service\EntryGroupDomainService;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupName;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\EntryGroupRepository;
use App\Shared\Application\Job\AbstractReplicableSyncJob;
use InvalidArgumentException;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;

final class RenameEntryGroupSyncJob extends AbstractReplicableSyncJob
{
    const string PAYLOAD_KEY_ID = EntryGroupRepository::FIELD_ID;
    const string PAYLOAD_KEY_NAME = EntryGroupRepository::FIELD_NAME;

    /**
     * @return EntryGroup
     * @throws PersistenceException
     */
    public function handle(): EntryGroup
    {
        $this->validate();

        $entryGroupDomainService = EntryGroupDomainService::getInstance();
        $entryGroupId = EntryGroupId::fromRaw($this->payload[self::PAYLOAD_KEY_ID]);
        $entryGroup = $entryGroupDomainService->findById($entryGroupId);
        if (is_null($entryGroup)) {
            throw new InvalidArgumentException('Entry Group not found');
        }
        $entryGroup->name = EntryGroupName::fromRaw($this->payload[self::PAYLOAD_KEY_NAME]);
        $entryGroupDomainService->save($entryGroup);

        EventDispatcher::getInstance()->dispatch(new EntryGroupRenamedEvent($entryGroup));

        return $entryGroup;
    }

    /**
     * @return void
     */
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