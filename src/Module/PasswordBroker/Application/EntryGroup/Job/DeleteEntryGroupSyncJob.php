<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryGroup\Job;

use App\Module\PasswordBroker\Application\EntryGroup\Event\EntryGroupDeletedEvent;
use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\EntryGroupRepository;
use App\Shared\Application\Job\AbstractReplicableSyncJob;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use InvalidArgumentException;

final class DeleteEntryGroupSyncJob extends AbstractReplicableSyncJob
{
    public const string PAYLOAD_KEY_ID = EntryGroupRepository::FIELD_ID;

    /**
     * @throws PersistenceException
     */
    #[\Override]
    public function handle(): EntryGroup
    {
        $this->validate();

        $entryGroupRepository = EntryGroupRepository::getInstance();
        $entryGroupId = EntryGroupId::fromRaw($this->payload[self::PAYLOAD_KEY_ID]);
        /**
         * @var EntryGroup $entryGroup
         */
        $entryGroup = $entryGroupRepository->findById($entryGroupId);
        if (is_null($entryGroup)) {
            throw new InvalidArgumentException('Entry Group not found');
        }
        $entryGroupRepository->removeById($entryGroup);

        EventDispatcher::getInstance()->dispatch(new EntryGroupDeletedEvent($entryGroup));

        return $entryGroup;
    }

    private function validate(): void
    {
        if (empty($this->payload[self::PAYLOAD_KEY_ID])) {
            throw new InvalidArgumentException('Entry Group id is required');
        }
    }
}
