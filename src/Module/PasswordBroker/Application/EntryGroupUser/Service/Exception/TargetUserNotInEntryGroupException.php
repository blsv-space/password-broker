<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception;

use App\Module\Identity\Domain\User\ValueObject\UserId;

class TargetUserNotInEntryGroupException extends EntryGroupUserApplicationException
{
    public function __construct(UserId $userId)
    {
        parent::__construct("Target user with ID {$userId->toRaw()} is not a member of the entry group");
    }
}
