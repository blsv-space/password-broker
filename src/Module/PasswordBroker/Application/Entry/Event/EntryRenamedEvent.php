<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\Entry\Event;

use App\Module\PasswordBroker\Application\Event\AbstractPasswordBrokerEvent;
use App\Module\PasswordBroker\Domain\Entry\Entity\Entry;
use Inquisition\Core\Application\Event\EventInterface;

final readonly class EntryRenamedEvent extends AbstractPasswordBrokerEvent implements EventInterface
{
    public function __construct(
        private Entry $entry,
    ) {
        parent::__construct();
    }

    public function getEntry(): Entry
    {
        return $this->entry;
    }

    #[\Override]
    public function getEventName(): string
    {
        return parent::getEventName() . '.entry.renamed';
    }

}
