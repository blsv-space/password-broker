<?php

namespace App\Module\PasswordBroker\Application\Event;

use DateTimeImmutable;
use Inquisition\Core\Application\Event\EventInterface;

readonly class AbstractPasswordBrokerEvent implements EventInterface
{
    private DateTimeImmutable $occurredOn;

    public function __construct()
    {
        $this->occurredOn = new DateTimeImmutable();
    }

    /**
     * @inheritDoc
     */
    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    /**
     * @inheritDoc
     */
    public function getEventName(): string
    {
        return 'PasswordBroker';
    }
}