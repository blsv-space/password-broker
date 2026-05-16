<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Job;

use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldPasswordCreatedEvent;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldPassword;
use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use Inquisition\Core\Application\Event\EventInterface;
use InvalidArgumentException;
use Override;

/**
 * @extends AbstractCreateEntryFieldSyncJob<EntryFieldPassword, EntryFieldPasswordCreatedEvent>
 */
final class CreateEntryFieldPasswordSyncJob extends AbstractCreateEntryFieldSyncJob
{
    public const string PAYLOAD_LOGIN = EntryFieldRepository::FIELD_LOGIN;
    #[Override]
    protected function getEvent(AbstractEntryField $entry): EventInterface
    {
        return new EntryFieldPasswordCreatedEvent($entry);
    }

    #[Override]
    protected function validateByFieldType(): void
    {
        if (empty($this->payload[self::PAYLOAD_LOGIN])) {
            throw new InvalidArgumentException('Entry Field Login is required');
        }
    }
}
