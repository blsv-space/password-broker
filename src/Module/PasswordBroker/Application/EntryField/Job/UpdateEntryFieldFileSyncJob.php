<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Job;

use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldFileCreatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldFileUpdatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Job\ValidateTrait\EntryFieldFiledValidate;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldFile;
use Inquisition\Core\Application\Event\EventInterface;
use Override;

/**
 * @extends AbstractUpdateEntryFieldSyncJob<EntryFieldFile, EntryFieldFileCreatedEvent>
 */
final class UpdateEntryFieldFileSyncJob extends AbstractUpdateEntryFieldSyncJob
{
    use EntryFieldFiledValidate;

    #[Override]
    protected function getEvent(AbstractEntryField $entry): EventInterface
    {
        return new EntryFieldFileUpdatedEvent($entry);
    }

    #[Override]
    protected function updateByEntryFieldType(AbstractEntryField $entry): void
    {
        $entry->fileMime = $this->payload[self::PAYLOAD_KEY_FILE_MIME];
        $entry->fileName = $this->payload[self::PAYLOAD_KEY_FILE_NAME];
        $entry->fileSize = $this->payload[self::PAYLOAD_KEY_FILE_SIZE];
    }
}
