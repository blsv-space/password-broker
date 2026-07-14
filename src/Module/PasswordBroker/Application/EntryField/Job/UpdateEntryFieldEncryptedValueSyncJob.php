<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Job;

use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldEncryptedValueUpdatedEvent;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use Inquisition\Core\Application\Event\EventInterface;
use Override;

/**
 * @extends AbstractUpdateEntryFieldSyncJob<AbstractEntryField, EntryFieldEncryptedValueUpdatedEvent>
 */
final class UpdateEntryFieldEncryptedValueSyncJob extends AbstractUpdateEntryFieldSyncJob
{
    #[Override]
    protected function getEvent(AbstractEntryField $entryField): EventInterface
    {
        return new EntryFieldEncryptedValueUpdatedEvent(
            entryField: $entryField,
            executorId: $this->payload[self::PAYLOAD_EXECUTED_BY],
        );
    }

    #[Override]
    protected function updateByEntryFieldType(AbstractEntryField $entryField): void {}


    #[Override]
    protected function validateByEntryFieldType(): void {}
}
