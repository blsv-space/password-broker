<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception;

class AuthUserHasNoRights extends EntryGroupUserApplicationException
{
    public function __construct()
    {
        parent::__construct('Authenticated user has no rights to add users to the entry group.');
    }
}
