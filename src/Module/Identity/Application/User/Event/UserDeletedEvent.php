<?php

namespace App\Module\Identity\Application\User\Event;

use App\Module\Identity\Application\Event\AbstractIdentityEvent;
use App\Module\Identity\Domain\User\Entity\User;
use Inquisition\Core\Application\Event\EventInterface;

final readonly class UserDeletedEvent extends AbstractIdentityEvent
    implements EventInterface
{
    public function __construct(
        private readonly User $user,
    )
    {
        parent::__construct();
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @inheritDoc
     */
    public function getEventName(): string
    {
        return parent::getEventName() . '.user.deleted';
    }
}