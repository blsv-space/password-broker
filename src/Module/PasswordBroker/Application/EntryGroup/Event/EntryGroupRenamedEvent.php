<?php

namespace App\Module\PasswordBroker\Application\EntryGroup\Event;

use App\Module\PasswordBroker\Application\Event\AbstractPasswordBrokerEvent;
use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use Inquisition\Core\Application\Event\EventInterface;

final readonly class EntryGroupRenamedEvent extends AbstractPasswordBrokerEvent
    implements EventInterface
{
    /**
     * @param EntryGroup $entryGroup
     */
    public function __construct(
        private EntryGroup $entryGroup,
    ) {
        parent::__construct();
    }

    /**
     * @return EntryGroup
     */
    public function getEntryGroup(): EntryGroup
    {
        return $this->entryGroup;
    }

    /**
     * @return string
     */
    public function getEventName(): string
    {
        return parent::getEventName() . '.entryGroup.renamed';
    }

}