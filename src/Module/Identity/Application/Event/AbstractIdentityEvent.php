<?php

declare(strict_types=1);

namespace App\Module\Identity\Application\Event;

use DateTimeImmutable;
use Inquisition\Core\Application\Event\EventInterface;

readonly class AbstractIdentityEvent implements EventInterface
{
    private DateTimeImmutable $occurredOn;

    public function __construct()
    {
        $this->occurredOn = new DateTimeImmutable();
    }

    #[\Override]
    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    #[\Override]
    public function getEventName(): string
    {
        return 'identity';
    }
}
