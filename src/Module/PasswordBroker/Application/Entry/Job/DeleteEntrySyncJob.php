<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\Entry\Job;

use App\Module\PasswordBroker\Application\Entry\Event\EntryDeletedEvent;
use App\Module\PasswordBroker\Domain\Entry\Entity\Entry;
use App\Module\PasswordBroker\Domain\Entry\ValueObject\EntryId;
use App\Module\PasswordBroker\Infrastructure\Entry\Repository\EntryRepository;
use App\Shared\Application\Job\AbstractReplicableSyncJob;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use InvalidArgumentException;

final class DeleteEntrySyncJob extends AbstractReplicableSyncJob
{
    public const string PAYLOAD_KEY_ID = EntryRepository::FIELD_ID;

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
        $entryRepository->softDelete($entry);

        EventDispatcher::getInstance()->dispatch(new EntryDeletedEvent($entry));

        return $entry;
    }

    private function validate(): void
    {
        if (empty($this->payload[self::PAYLOAD_KEY_ID])) {
            throw new InvalidArgumentException('Entry id is required');
        }
    }
}
