<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\Entry\Job;

use App\Module\PasswordBroker\Application\Entry\Event\EntryMovedEvent;
use App\Module\PasswordBroker\Domain\Entry\Entity\Entry;
use App\Module\PasswordBroker\Domain\Entry\ValueObject\EntryId;
use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Infrastructure\Entry\Repository\EntryRepository;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\Repository\EntryGroupRepository;
use App\Shared\Application\Job\AbstractReplicableSyncJob;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use InvalidArgumentException;

final class MoveEntrySyncJob extends AbstractReplicableSyncJob
{
    public const string PAYLOAD_KEY_ID = EntryRepository::FIELD_ID;
    public const string PAYLOAD_KEY_ENTRY_GROUP_TARGET_ID = EntryRepository::FIELD_ENTRY_GROUP_ID;
    public const string PAYLOAD_KEY_ENTRY_GROUP_ORIGIN_AES_PASSWORD = 'originAesPassword';
    public const string PAYLOAD_KEY_ENTRY_GROUP_TARGET_AES_PASSWORD = 'targetAesPassword';
    public const string PAYLOAD_UPDATED_AT = EntryRepository::FIELD_UPDATED_AT;


    /**
     * @throws PersistenceException
     */
    #[\Override]
    public function handle(): Entry
    {
        $this->validate();

        $entryRepository = EntryRepository::getInstance();
        $entryGroupRepository = EntryGroupRepository::getInstance();
        $entryId = EntryId::fromRaw($this->payload[self::PAYLOAD_KEY_ID]);
        /**
         * @var Entry $entry
         */
        $entry = $entryRepository->findById($entryId);
        if (is_null($entry)) {
            throw new InvalidArgumentException('Entry not found');
        }
        $entryGroupId = EntryGroupId::fromRaw($this->payload[self::PAYLOAD_KEY_ENTRY_GROUP_TARGET_ID]);
        /**
         * @var EntryGroup $entryGroup
         */
        $entryGroup = $entryGroupRepository->findById($entryGroupId);
        if (is_null($entryGroup)) {
            throw new InvalidArgumentException('Entry Group not found');
        }

        $entry->entryGroupId = EntryGroupId::fromRaw($this->payload[self::PAYLOAD_KEY_ENTRY_GROUP_TARGET_ID]);

        $entryRepository->beginTransaction();

        /**
         * @TODO create and handle reencryptions for all fields in the Entry
         */


        $entryRepository->save($entry);

        $entryRepository->commit();

        EventDispatcher::getInstance()->dispatch(new EntryMovedEvent($entry));

        return $entry;
    }

    private function validate(): void
    {
        if (empty($this->payload[self::PAYLOAD_KEY_ID])) {
            throw new InvalidArgumentException('Entry id is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_ENTRY_GROUP_TARGET_ID])) {
            throw new InvalidArgumentException('Entry Group id is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_ENTRY_GROUP_ORIGIN_AES_PASSWORD])) {
            throw new InvalidArgumentException('Origin Group AES password is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_ENTRY_GROUP_TARGET_AES_PASSWORD])) {
            throw new InvalidArgumentException('Target Group AES password is required');
        }

        if (empty($this->payload[self::PAYLOAD_UPDATED_AT])
            || !is_string($this->payload[self::PAYLOAD_UPDATED_AT])
        ) {
            throw new InvalidArgumentException('UpdatedAt is required');
        }
    }
}
