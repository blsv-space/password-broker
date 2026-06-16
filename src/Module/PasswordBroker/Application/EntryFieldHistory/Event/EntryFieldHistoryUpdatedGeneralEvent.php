<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\Event;

use App\Module\PasswordBroker\Application\Event\AbstractPasswordBrokerEvent;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\AbstractEntryFieldHistory;
use Inquisition\Core\Application\Event\EventInterface;

final readonly class EntryFieldHistoryUpdatedGeneralEvent extends AbstractPasswordBrokerEvent implements EventInterface
{
    public function __construct(
        private AbstractEntryFieldHistory $entryFieldHistory,
    ) {
        parent::__construct();
    }

    public function getEntry(): AbstractEntryFieldHistory
    {
        return $this->entryFieldHistory;
    }

    #[\Override]
    public function getEventName(): string
    {
        return parent::getEventName() . '.entryFieldHistory.general.updated';
    }

}
