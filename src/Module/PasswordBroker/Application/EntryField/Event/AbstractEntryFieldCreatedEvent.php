<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Event;

use App\Module\PasswordBroker\Application\Event\AbstractPasswordBrokerEvent;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;

/**
 * @template T of AbstractEntryField
 */
abstract readonly class AbstractEntryFieldCreatedEvent extends AbstractPasswordBrokerEvent
{
    /**
     * @param T $entryField
     */
    public function __construct(
        protected AbstractEntryField $entryField,
    ) {
        parent::__construct();
    }

    /**
     * @psalm-return T
     */
    public function getEntry(): AbstractEntryField
    {
        return $this->entryField;
    }

}
