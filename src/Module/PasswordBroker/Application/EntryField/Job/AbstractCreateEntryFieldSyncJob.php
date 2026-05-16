<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Job;

use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldCreatedGeneralEvent;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldId;
use App\Module\PasswordBroker\Infrastructure\Entry\Repository\EntryRepository;
use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use App\Shared\Application\Job\AbstractReplicableSyncJob;
use Inquisition\Core\Application\Event\EventInterface;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use InvalidArgumentException;

/**
 * @template T of AbstractEntryField
 * @template U of EventInterface
 */
abstract class AbstractCreateEntryFieldSyncJob extends AbstractReplicableSyncJob
{
    public const string PAYLOAD_KEY_ID = EntryFieldRepository::FIELD_ID;
    public const string PAYLOAD_ENTRY_ID = EntryFieldRepository::FIELD_ENTRY_ID;
    public const string PAYLOAD_FIELD_TYPE = EntryFieldRepository::FIELD_TYPE;
    public const string PAYLOAD_KEY_TITLE = EntryFieldRepository::FIELD_TITLE;
    public const string PAYLOAD_VALUE_ENCRYPTED = EntryFieldRepository::FIELD_VALUE_ENCRYPTED;
    public const string PAYLOAD_FIELD_TAG = EntryFieldRepository::FIELD_TAG;
    public const string PAYLOAD_FIELD_INITIALIZATION_VECTOR = EntryFieldRepository::FIELD_INITIALIZATION_VECTOR;
    public const string PAYLOAD_CREATED_AT = EntryFieldRepository::FIELD_CREATED_AT;
    public const string PAYLOAD_CREATED_BY = EntryFieldRepository::FIELD_CREATED_BY;

    /**
     * @throws PersistenceException
     * @psalm-return T
     */
    #[\Override]
    public function handle(): AbstractEntryField
    {
        $this->validate();

        $entryFieldRepository = EntryFieldRepository::getInstance();
        $entryRepository = EntryRepository::getInstance();
        /**
         * @var T $entryField
         */
        $entryField = $entryFieldRepository->mapArrayToEntity($this->payload);

        $entry = $entryRepository->findById(
            EntryFieldId::fromRaw($this->payload[self::PAYLOAD_ENTRY_ID]),
        );
        if (!$entry) {
            throw new InvalidArgumentException('Entry not found');
        }

        $entryFieldRepository->save($entryField);
        EventDispatcher::getInstance()->dispatch($this->getEvent($entryField));
        EventDispatcher::getInstance()->dispatch(new EntryFieldCreatedGeneralEvent($entryField));

        return $entryField;
    }

    /**
     * @param  T $entry
     * @return U
     */
    abstract protected function getEvent(AbstractEntryField $entry): EventInterface;

    abstract protected function validateByFieldType(): void;

    protected function validate(): void
    {
        $this->validateByFieldType();

        if (empty($this->payload[self::PAYLOAD_KEY_ID])) {
            throw new InvalidArgumentException('Entry Field id is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_TITLE])) {
            throw new InvalidArgumentException('Entry Field Title is required');
        }

        if (empty($this->payload[self::PAYLOAD_ENTRY_ID])) {
            throw new InvalidArgumentException('Entry Field Entry id is required');
        }

        if (empty($this->payload[self::PAYLOAD_FIELD_TYPE])) {
            throw new InvalidArgumentException('Entry Field Type is required');
        }

        if (empty($this->payload[self::PAYLOAD_VALUE_ENCRYPTED])) {
            throw new InvalidArgumentException('Entry Field Value Encrypted is required');
        }

        if (empty($this->payload[self::PAYLOAD_FIELD_TAG])) {
            throw new InvalidArgumentException('Entry Field Tag is required');
        }

        if (empty($this->payload[self::PAYLOAD_FIELD_INITIALIZATION_VECTOR])) {
            throw new InvalidArgumentException('Entry Field Initialization Vector is required');
        }

        if (empty($this->payload[self::PAYLOAD_CREATED_AT])
            || !is_string($this->payload[self::PAYLOAD_CREATED_AT])
        ) {
            throw new InvalidArgumentException('CreatedAt is required');
        }

        if (empty($this->payload[self::PAYLOAD_CREATED_BY])) {
            throw new InvalidArgumentException('createdBy id is required');
        }
    }

}
