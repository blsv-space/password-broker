<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\Entry\Job;

use App\Module\PasswordBroker\Application\Entry\Event\EntryCreatedEvent;
use App\Module\PasswordBroker\Domain\Entry\Entity\Entry;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Infrastructure\Entry\Repository\EntryRepository;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\Repository\EntryGroupRepository;
use App\Shared\Application\Job\AbstractReplicableSyncJob;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use InvalidArgumentException;

final class CreateEntrySyncJob extends AbstractReplicableSyncJob
{
    public const string PAYLOAD_KEY_ID = EntryRepository::FIELD_ID;
    public const string PAYLOAD_KEY_TITLE = EntryRepository::FIELD_TITLE;
    public const string PAYLOAD_KEY_ENTRY_GROUP_ID = EntryRepository::FIELD_ENTRY_GROUP_ID;
    public const string PAYLOAD_CREATED_AT = EntryRepository::FIELD_CREATED_AT;

    /**
     * @throws PersistenceException
     */
    #[\Override]
    public function handle(): Entry
    {
        $this->validate();

        $entryRepository = EntryRepository::getInstance();
        $entryGroupRepository = EntryGroupRepository::getInstance();
        $entry = $entryRepository->mapArrayToEntity($this->payload);

        $entryGroup = $entryGroupRepository->findById(EntryGroupId::fromRaw($this->payload[self::PAYLOAD_KEY_ENTRY_GROUP_ID]));
        if (!$entryGroup) {
            throw new InvalidArgumentException('Entry Group not found');
        }

        $entryRepository->save($entry);
        EventDispatcher::getInstance()->dispatch(new EntryCreatedEvent($entry));

        return $entry;
    }

    private function validate(): void
    {
        if (empty($this->payload[self::PAYLOAD_KEY_ID])) {
            throw new InvalidArgumentException('Entry id is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_TITLE])) {
            throw new InvalidArgumentException('Entry EntryTitle is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_ENTRY_GROUP_ID])) {
            throw new InvalidArgumentException('Entry Group id is required');
        }

        if (empty($this->payload[self::PAYLOAD_CREATED_AT])
            || !is_string($this->payload[self::PAYLOAD_CREATED_AT])
        ) {
            throw new InvalidArgumentException('CreatedAt is required');
        }
    }

}
