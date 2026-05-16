<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Job;

use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldFileCreatedEvent;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldFile;
use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use Inquisition\Core\Application\Event\EventInterface;
use InvalidArgumentException;
use Override;

/**
 * @extends AbstractCreateEntryFieldSyncJob<EntryFieldFile, EntryFieldFileCreatedEvent>
 */
final class CreateEntryFieldFileSyncJob extends AbstractCreateEntryFieldSyncJob
{
    public const string PAYLOAD_FILE_NAME = EntryFieldRepository::FIELD_FILE_NAME;
    public const string PAYLOAD_FILE_SIZE = EntryFieldRepository::FIELD_FILE_SIZE;
    public const string PAYLOAD_FILE_MIME = EntryFieldRepository::FIELD_FILE_MIME;

    #[Override]
    protected function getEvent(AbstractEntryField $entry): EventInterface
    {
        return new EntryFieldFileCreatedEvent($entry);
    }

    #[Override]
    protected function validateByFieldType(): void
    {
        if (empty($this->payload[self::PAYLOAD_FILE_NAME])) {
            throw new InvalidArgumentException('Entry Field File Name is required');
        };
        if (empty($this->payload[self::PAYLOAD_FILE_SIZE])
            && !is_int($this->payload[self::PAYLOAD_FILE_SIZE])
            && $this->payload[self::PAYLOAD_FILE_SIZE] <= 0
        ) {
            throw new InvalidArgumentException('Entry Field File Size is required');
        };
        if (empty($this->payload[self::PAYLOAD_FILE_MIME])) {
            throw new InvalidArgumentException('Entry Field File Name is required');
        };
    }
}
