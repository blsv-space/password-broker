<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\Event;

use App\Module\PasswordBroker\Application\Event\AbstractPasswordBrokerEvent;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\AbstractEntryFieldHistory;

/**
 * @template T of AbstractEntryFieldHistory
 */
abstract readonly class AbstractEntryFieldHistoryUpdatedEvent extends AbstractPasswordBrokerEvent
{
    public function __construct(
        protected AbstractEntryFieldHistory $entryFieldHistory,
    ) {
        parent::__construct();
    }

    /**
     * @psalm-return AbstractEntryFieldHistory
     */
    public function getEntry(): AbstractEntryFieldHistory
    {
        return $this->entryFieldHistory;
    }

}
