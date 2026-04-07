<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception;

use App\Module\Identity\Domain\User\ValueObject\UserId;

class TargetUserNotFoundException extends EntryGroupUserApplicationException
{
    public function __construct(UserId $userId)
    {
        parent::__construct("Target user with ID {$userId->toRaw()} not found");
    }
}
