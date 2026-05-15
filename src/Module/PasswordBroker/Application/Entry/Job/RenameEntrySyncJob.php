<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\Entry\Job;

use App\Module\PasswordBroker\Application\Entry\Event\EntryRenamedEvent;
use App\Module\PasswordBroker\Domain\Entry\Entity\Entry;
use App\Module\PasswordBroker\Domain\Entry\ValueObject\EntryId;
use App\Module\PasswordBroker\Domain\Entry\ValueObject\EntryTitle;
use App\Module\PasswordBroker\Infrastructure\Entry\Repository\EntryRepository;
use App\Shared\Application\Job\AbstractReplicableSyncJob;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use InvalidArgumentException;

final class RenameEntrySyncJob extends AbstractReplicableSyncJob
{
    public const string PAYLOAD_KEY_ID = EntryRepository::FIELD_ID;
    public const string PAYLOAD_KEY_TITLE = EntryRepository::FIELD_TITLE;
    public const string PAYLOAD_UPDATED_AT = EntryRepository::FIELD_UPDATED_AT;

    /**
     * @throws PersistenceException
     */
    #[\Override]
    public function handle(): Entry
    {
        $this->validate();

        $entryRepository = EntryRepository::getInstance();
        $entryId = EntryId::fromRaw($this->payload[self::PAYLOAD_KEY_ID]);
        /**
         * @var Entry $entry
         */
        $entry = $entryRepository->findById($entryId);
        if (is_null($entry)) {
            throw new InvalidArgumentException('Entry not found');
        }
        $entry->title = EntryTitle::fromRaw($this->payload[self::PAYLOAD_KEY_TITLE]);
        $entryRepository->save($entry);

        EventDispatcher::getInstance()->dispatch(new EntryRenamedEvent($entry));

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

        if (empty($this->payload[self::PAYLOAD_UPDATED_AT])
            || !is_string($this->payload[self::PAYLOAD_UPDATED_AT])
        ) {
            throw new InvalidArgumentException('UpdatedAt is required');
        }
    }
}
