<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Job;

use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldUpdatedGeneralEvent;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
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
abstract class AbstractUpdateEntryFieldSyncJob extends AbstractReplicableSyncJob
{
    public const string PAYLOAD_KEY_ID = EntryFieldRepository::FIELD_ID;
    public const string PAYLOAD_KEY_TITLE = EntryFieldRepository::FIELD_TITLE;
    public const string PAYLOAD_KEY_VALUE_ENCRYPTED = EntryFieldRepository::FIELD_VALUE_ENCRYPTED;
    public const string PAYLOAD_KEY_FIELD_TAG = EntryFieldRepository::FIELD_TAG;
    public const string PAYLOAD_KEY_FIELD_INITIALIZATION_VECTOR = EntryFieldRepository::FIELD_INITIALIZATION_VECTOR;
    public const string PAYLOAD_KEY_UPDATED_AT = EntryFieldRepository::FIELD_UPDATED_AT;
    public const string PAYLOAD_KEY_UPDATED_BY = EntryFieldRepository::FIELD_UPDATED_BY;

    /**
     * @throws PersistenceException
     * @psalm-return AbstractEntryField
     */
    #[\Override]
    public function handle(): AbstractEntryField
    {
        $this->validate();

        $entryFieldRepository = EntryFieldRepository::getInstance();

        /**
         * @var T $entryField
         */
        $entryField = $entryFieldRepository->findById($this->payload[self::PAYLOAD_KEY_ID]);
        if (is_null($entryField)) {
            throw new InvalidArgumentException("Entry Field with ID {$this->payload[self::PAYLOAD_KEY_ID]} not found");
        }
        $entryField->title = $this->payload[self::PAYLOAD_KEY_TITLE];
        if (isset($this->payload[self::PAYLOAD_KEY_VALUE_ENCRYPTED])) {
            $entryField->valueEncrypted = $this->payload[self::PAYLOAD_KEY_VALUE_ENCRYPTED];
            $entryField->tag = $this->payload[self::PAYLOAD_KEY_FIELD_TAG];
            $entryField->initializationVector = $this->payload[self::PAYLOAD_KEY_FIELD_INITIALIZATION_VECTOR];
        }
        $entryField->updatedAt = $this->payload[self::PAYLOAD_KEY_UPDATED_AT];
        $entryField->updatedBy = $this->payload[self::PAYLOAD_KEY_UPDATED_BY];
        $this->updateByEntryFieldType($entryField);

        $entryFieldRepository->save($entryField);
        EventDispatcher::getInstance()->dispatch($this->getEvent($entryField));
        EventDispatcher::getInstance()->dispatch(new EntryFieldUpdatedGeneralEvent($entryField));

        return $entryField;
    }

    abstract protected function getEvent(AbstractEntryField $entry): EventInterface;

    abstract protected function validateByEntryFieldType(): void;

    /**
     * @psalm-param T $entry
     */
    abstract protected function updateByEntryFieldType(AbstractEntryField $entry): void;

    protected function validate(): void
    {
        $this->validateByEntryFieldType();

        if (empty($this->payload[self::PAYLOAD_KEY_ID])) {
            throw new InvalidArgumentException('Entry Field id is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_TITLE])) {
            throw new InvalidArgumentException('Entry Field Title is required');
        }

        if (!empty($this->payload[self::PAYLOAD_KEY_VALUE_ENCRYPTED])) {
            if (empty($this->payload[self::PAYLOAD_KEY_FIELD_TAG])) {
                throw new InvalidArgumentException('Entry Field Tag is required');
            }

            if (empty($this->payload[self::PAYLOAD_KEY_FIELD_INITIALIZATION_VECTOR])) {
                throw new InvalidArgumentException('Entry Field Initialization Vector is required');
            }
        }

        if (empty($this->payload[self::PAYLOAD_KEY_UPDATED_AT])
            || !is_string($this->payload[self::PAYLOAD_KEY_UPDATED_AT])
        ) {
            throw new InvalidArgumentException('updatedAt is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_UPDATED_BY])) {
            throw new InvalidArgumentException('updatedBy id is required');
        }
    }

}
