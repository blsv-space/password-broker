<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Job;

use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldDeletedEvent;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldId;
use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use App\Shared\Application\Job\AbstractReplicableSyncJob;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use InvalidArgumentException;

final class DeleteEntryFieldSyncJob extends AbstractReplicableSyncJob
{
    public const string PAYLOAD_KEY_ID = EntryFieldRepository::FIELD_ID;
    public const string PAYLOAD_EXECUTED_BY = 'executedBy';

    /**
     * @throws PersistenceException
     */
    #[\Override]
    public function handle(): AbstractEntryField
    {
        $this->validate();

        $entryFieldRepository = EntryFieldRepository::getInstance();
        $entryFieldId = EntryFieldId::fromRaw($this->payload[self::PAYLOAD_KEY_ID]);
        /**
         * @var AbstractEntryField $entryField
         */
        $entryField = $entryFieldRepository->findById($entryFieldId);
        if (is_null($entryField)) {
            throw new InvalidArgumentException('Entry Field not found');
        }
        $entryFieldRepository->softDelete($entryField);

        EventDispatcher::getInstance()->dispatch(new EntryFieldDeletedEvent(
            entryField: $entryField,
            executorId: $this->payload[self::PAYLOAD_EXECUTED_BY],
        ));

        return $entryField;
    }

    private function validate(): void
    {
        if (empty($this->payload[self::PAYLOAD_KEY_ID])) {
            throw new InvalidArgumentException('Entry Field id is required');
        }
        if (empty($this->payload[self::PAYLOAD_EXECUTED_BY])) {
            throw new InvalidArgumentException('Executer user id is required');
        }
        UserId::validate($this->payload[self::PAYLOAD_EXECUTED_BY]);
    }
}
