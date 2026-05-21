<?php

declare(strict_types=1);

namespace App\Module\Identity\Application\User\Service\Exception;

class AuthInvalidPasswordException extends AuthException
{
    public function __construct(string $username)
    {
        parent::__construct('Invalid password for user: ' . $username);
    }

}
