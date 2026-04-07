<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception;

class AuthUserNotInEntryGroupException extends EntryGroupUserApplicationException
{
    public function __construct()
    {
        parent::__construct('Authenticated user is not a member of the entry group');
    }
}
