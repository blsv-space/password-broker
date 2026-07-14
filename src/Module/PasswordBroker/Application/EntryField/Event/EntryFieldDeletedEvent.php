<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Event;

use App\Module\PasswordBroker\Application\Event\AbstractPasswordBrokerEvent;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use Inquisition\Core\Application\Event\EventInterface;

final readonly class EntryFieldDeletedEvent extends AbstractPasswordBrokerEvent implements EventInterface
{
    public function __construct(
        private AbstractEntryField $entryField,
        private string $executorId,
    ) {
        parent::__construct();
    }

    public function getEntry(): AbstractEntryField
    {
        return $this->entryField;
    }

    public function getExecutorId(): string
    {
        return $this->executorId;
    }

    #[\Override]
    public function getEventName(): string
    {
        return parent::getEventName() . '.entryField.deleted';
    }

}
