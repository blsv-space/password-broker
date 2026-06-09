<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Job;

use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldEncryptedValueUpdatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldFileCreatedEvent;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use Inquisition\Core\Application\Event\EventInterface;
use Override;

/**
 * @extends AbstractUpdateEntryFieldSyncJob<AbstractEntryField, EntryFieldFileCreatedEvent>
 */
final class UpdateEntryFieldEncryptedValueSyncJob extends AbstractUpdateEntryFieldSyncJob
{
    #[Override]
    protected function getEvent(AbstractEntryField $entry): EventInterface
    {
        return new EntryFieldEncryptedValueUpdatedEvent($entry);
    }

    #[Override]
    protected function updateByEntryFieldType(AbstractEntryField $entry): void {}


    #[Override]
    protected function validateByEntryFieldType(): void {}
}
