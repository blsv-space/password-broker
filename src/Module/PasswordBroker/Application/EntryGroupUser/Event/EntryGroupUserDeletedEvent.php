<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryGroupUser\Event;

use App\Module\PasswordBroker\Application\Event\AbstractPasswordBrokerEvent;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Entity\EntryGroupUser;
use Inquisition\Core\Application\Event\EventInterface;

final readonly class EntryGroupUserDeletedEvent extends AbstractPasswordBrokerEvent implements EventInterface
{
    public function __construct(
        private EntryGroupUser $entryGroupUser,
    ) {
        parent::__construct();
    }

    public function getEntryGroupUser(): EntryGroupUser
    {
        return $this->entryGroupUser;
    }

    #[\Override]
    public function getEventName(): string
    {
        return parent::getEventName() . '.entryGroupUser.deleted';
    }

}
