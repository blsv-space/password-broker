<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Job;

use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldLinkCreatedEvent;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldLink;
use Inquisition\Core\Application\Event\EventInterface;
use Override;

/**
 * @extends AbstractCreateEntryFieldSyncJob<EntryFieldLink, EntryFieldLinkCreatedEvent>
 */
final class CreateEntryFieldLinkSyncJob extends AbstractCreateEntryFieldSyncJob
{
    #[Override]
    protected function getEvent(AbstractEntryField $entry): EventInterface
    {
        return new EntryFieldLinkCreatedEvent($entry);
    }

    #[Override]
    protected function validateByFieldType(): void {}
}
