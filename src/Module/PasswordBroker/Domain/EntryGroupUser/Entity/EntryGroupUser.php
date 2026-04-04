<?php

namespace App\Module\PasswordBroker\Domain\EntryGroupUser\Entity;

use Inquisition\Core\Domain\Entity\BaseEntityWithId;
use Inquisition\Core\Domain\Entity\EntityInterface;
use Inquisition\Core\Domain\ValueObject\ValueObjectInterface;

class EntryGroupUser extends BaseEntityWithId implements EntityInterface
{

    public function getId(): ?ValueObjectInterface
    {
        // TODO: Implement getId() method.
    }
}