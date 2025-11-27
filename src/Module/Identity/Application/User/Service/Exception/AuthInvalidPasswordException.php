<?php

namespace App\Module\Identity\Application\User\Service\Exception;

use JetBrains\PhpStorm\Pure;

class AuthInvalidPasswordException extends AuthException
{
    #[Pure]
    public function __construct(string $username)
    {
        parent::__construct('Invalid password for user: ' . $username);
    }

}