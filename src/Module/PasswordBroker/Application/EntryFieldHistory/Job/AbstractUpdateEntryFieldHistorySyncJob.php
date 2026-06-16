<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\Job;

use App\Module\PasswordBroker\Application\EntryFieldHistory\Event\EntryFieldHistoryUpdatedGeneralEvent;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldInitializationVector;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTag;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldValueEncrypted;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\AbstractEntryFieldHistory;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\ValueObject\EntryFieldHistoryId;
use App\Module\PasswordBroker\Infrastructure\EntryFieldHistory\Repository\EntryFieldHistoryRepository;
use App\Shared\Application\Job\AbstractReplicableSyncJob;
use Inquisition\Core\Application\Event\EventInterface;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use InvalidArgumentException;

/**
 * @template T of AbstractEntryFieldHistory
 * @template U of EventInterface
 */
abstract class AbstractUpdateEntryFieldHistorySyncJob extends AbstractReplicableSyncJob
{
    public const string PAYLOAD_KEY_ID = EntryFieldHistoryRepository::FIELD_ID;
    public const string PAYLOAD_KEY_VALUE_ENCRYPTED = EntryFieldHistoryRepository::FIELD_VALUE_ENCRYPTED;
    public const string PAYLOAD_KEY_TAG = EntryFieldHistoryRepository::FIELD_TAG;
    public const string PAYLOAD_KEY_INITIALIZATION_VECTOR = EntryFieldHistoryRepository::FIELD_INITIALIZATION_VECTOR;

    /**
     * @throws PersistenceException
     * @psalm-return AbstractEntryFieldHistory
     */
    #[\Override]
    public function handle(): AbstractEntryFieldHistory
    {
        $this->validate();

        $entryFieldHistoryRepository = EntryFieldHistoryRepository::getInstance();

        /**
         * @var T $entryFieldHistory
         */
        $entryFieldHistory = $entryFieldHistoryRepository->findById(EntryFieldHistoryId::fromRaw($this->payload[self::PAYLOAD_KEY_ID]));
        if (is_null($entryFieldHistory)) {
            throw new InvalidArgumentException("Entry Field History with ID {$this->payload[self::PAYLOAD_KEY_ID]} not found");
        }

        if (isset($this->payload[self::PAYLOAD_KEY_VALUE_ENCRYPTED])) {
            $entryFieldHistory->valueEncrypted = EntryFieldValueEncrypted::fromRaw($this->payload[self::PAYLOAD_KEY_VALUE_ENCRYPTED]);
            $entryFieldHistory->tag = EntryFieldTag::fromRaw($this->payload[self::PAYLOAD_KEY_TAG]);
            $entryFieldHistory->initializationVector = EntryFieldInitializationVector::fromRaw($this->payload[self::PAYLOAD_KEY_INITIALIZATION_VECTOR]);
        }
        $this->updateByEntryFieldHistoryType($entryFieldHistory);

        $entryFieldHistoryRepository->save($entryFieldHistory);
        EventDispatcher::getInstance()->dispatch($this->getEvent($entryFieldHistory));
        EventDispatcher::getInstance()->dispatch(new EntryFieldHistoryUpdatedGeneralEvent(
            entryFieldHistory: $entryFieldHistory,
        ));

        return $entryFieldHistory;
    }

    /**
     * @psalm-param T $entryField
     * @psalm-return U
     */
    abstract protected function getEvent(AbstractEntryFieldHistory $entryFieldHistory): EventInterface;

    abstract protected function validateByEntryFieldType(): void;

    /**
     * @psalm-param T $entryFieldHistory
     */
    abstract protected function updateByEntryFieldHistoryType(AbstractEntryFieldHistory $entryFieldHistory): void;

    protected function validate(): void
    {
        if (empty($this->payload[self::PAYLOAD_KEY_ID])) {
            throw new InvalidArgumentException('Entry Field id is required');
        }

        if (!empty($this->payload[self::PAYLOAD_KEY_VALUE_ENCRYPTED])) {
            if (empty($this->payload[self::PAYLOAD_KEY_TAG])) {
                throw new InvalidArgumentException('Entry Field Tag is required');
            }

            if (empty($this->payload[self::PAYLOAD_KEY_INITIALIZATION_VECTOR])) {
                throw new InvalidArgumentException('Entry Field Initialization Vector is required');
            }

            $this->validateByEntryFieldType();
        }
    }

}
