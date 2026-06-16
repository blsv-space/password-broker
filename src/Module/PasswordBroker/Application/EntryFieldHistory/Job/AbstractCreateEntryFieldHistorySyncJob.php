<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\Job;

use App\Module\PasswordBroker\Application\EntryFieldHistory\Event\EntryFieldHistoryCreatedGeneralEvent;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldId;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\AbstractEntryFieldHistory;
use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use App\Module\PasswordBroker\Infrastructure\EntryFieldHistory\Repository\EntryFieldHistoryRepository;
use Inquisition\Core\Application\Event\EventInterface;
use Inquisition\Core\Application\Job\AbstractSyncJob;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use InvalidArgumentException;

/**
 * @template T of AbstractEntryFieldHistory
 * @template U of EventInterface
 */
abstract class AbstractCreateEntryFieldHistorySyncJob extends AbstractSyncJob
{
    public const string PAYLOAD_KEY_ID = EntryFieldHistoryRepository::FIELD_ID;
    public const string PAYLOAD_KEY_ENTRY_FIELD_ID = EntryFieldHistoryRepository::FIELD_ENTRY_FIELD_ID;
    public const string PAYLOAD_KEY_EVENT_NAME = EntryFieldHistoryRepository::FIELD_EVENT_NAME;
    public const string PAYLOAD_KEY_TITLE = EntryFieldHistoryRepository::FIELD_TITLE;
    public const string PAYLOAD_KEY_FIELD_TYPE = EntryFieldHistoryRepository::FIELD_TYPE;
    public const string PAYLOAD_KEY_VALUE_ENCRYPTED = EntryFieldHistoryRepository::FIELD_VALUE_ENCRYPTED;
    public const string PAYLOAD_KEY_TAG = EntryFieldHistoryRepository::FIELD_TAG;
    public const string PAYLOAD_KEY_INITIALIZATION_VECTOR = EntryFieldHistoryRepository::FIELD_INITIALIZATION_VECTOR;
    public const string PAYLOAD_KEY_IS_DELETED = EntryFieldHistoryRepository::FIELD_IS_DELETED;
    public const string PAYLOAD_KEY_CREATED_AT = EntryFieldHistoryRepository::FIELD_CREATED_AT;
    public const string PAYLOAD_KEY_CREATED_BY = EntryFieldHistoryRepository::FIELD_CREATED_BY;

    /**
     * @throws PersistenceException
     * @psalm-return AbstractEntryFieldHistory
     */
    #[\Override]
    public function handle(): AbstractEntryFieldHistory
    {
        $this->validate();

        $entryFieldHistoryRepository = EntryFieldHistoryRepository::getInstance();
        $entryFieldRepository = EntryFieldRepository::getInstance();
        $entryFieldHistory = $entryFieldHistoryRepository->mapArrayToEntity($this->payload);

        $entryField = $entryFieldRepository->findById(
            EntryFieldId::fromRaw($this->payload[self::PAYLOAD_KEY_ENTRY_FIELD_ID]),
        );
        if (!$entryField) {
            throw new InvalidArgumentException('Entry Field not found');
        }

        $entryFieldHistoryRepository->save($entryFieldHistory);
        EventDispatcher::getInstance()->dispatch($this->getEvent($entryFieldHistory));
        EventDispatcher::getInstance()->dispatch(new EntryFieldHistoryCreatedGeneralEvent(
            entryFieldHistory: $entryFieldHistory,
        ));

        return $entryFieldHistory;
    }

    /**
     * @psalm-return U
     */
    abstract protected function getEvent(AbstractEntryFieldHistory $entryFieldHistory): EventInterface;

    abstract protected function validateByEntryFieldType(): void;

    protected function validate(): void
    {
        $this->validateByEntryFieldType();

        if (empty($this->payload[self::PAYLOAD_KEY_ID])) {
            throw new InvalidArgumentException('Entry Field id is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_TITLE])) {
            throw new InvalidArgumentException('Entry Field Title is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_ENTRY_FIELD_ID])) {
            throw new InvalidArgumentException('Entry Field Entry Field id is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_EVENT_NAME])) {
            throw new InvalidArgumentException('Entry Field Event Name is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_FIELD_TYPE])) {
            throw new InvalidArgumentException('Entry Field Type is required');
        }

        if (!in_array($this->payload[self::PAYLOAD_KEY_FIELD_TYPE], EntryFieldTypeEnum::toArray(), true)) {
            throw new InvalidArgumentException('Entry Field Type is invalid');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_VALUE_ENCRYPTED])) {
            throw new InvalidArgumentException('Entry Field Value Encrypted is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_TAG])) {
            throw new InvalidArgumentException('Entry Field Tag is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_INITIALIZATION_VECTOR])) {
            throw new InvalidArgumentException('Entry Field Initialization Vector is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_IS_DELETED])) {
            throw new InvalidArgumentException('Entry Field Is Deleted is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_CREATED_AT])
            || !is_string($this->payload[self::PAYLOAD_KEY_CREATED_AT])
        ) {
            throw new InvalidArgumentException('CreatedAt is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_CREATED_BY])) {
            throw new InvalidArgumentException('createdBy id is required');
        }
    }

}
