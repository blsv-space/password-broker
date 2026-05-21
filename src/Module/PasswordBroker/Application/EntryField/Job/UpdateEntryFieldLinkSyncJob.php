<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Job;

use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldLinkCreatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldLinkUpdatedEvent;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldLink;
use Inquisition\Core\Application\Event\EventInterface;
use Override;

/**
 * @extends AbstractUpdateEntryFieldSyncJob<EntryFieldLink, EntryFieldLinkCreatedEvent>
 */
final class UpdateEntryFieldLinkSyncJob extends AbstractUpdateEntryFieldSyncJob
{
    #[Override]
    protected function getEvent(AbstractEntryField $entry): EventInterface
    {
        return new EntryFieldLinkUpdatedEvent($entry);
    }

    #[Override]
    protected function validateByEntryFieldType(): void {}

    #[Override]
    protected function updateByEntryFieldType(AbstractEntryField $entry): void {}
}
