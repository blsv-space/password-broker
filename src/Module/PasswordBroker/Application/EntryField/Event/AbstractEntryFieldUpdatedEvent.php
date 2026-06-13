<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Event;

use App\Module\PasswordBroker\Application\Event\AbstractPasswordBrokerEvent;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;

/**
 * @template T of AbstractEntryField
 */
abstract readonly class AbstractEntryFieldUpdatedEvent extends AbstractPasswordBrokerEvent
{
    public function __construct(
        protected AbstractEntryField $entryField,
        protected string $executorId,
    ) {
        parent::__construct();
    }

    /**
     * @psalm-return AbstractEntryField
     */
    public function getEntry(): AbstractEntryField
    {
        return $this->entryField;
    }

    public function getExecutorId(): string
    {
        return $this->executorId;
    }

}
