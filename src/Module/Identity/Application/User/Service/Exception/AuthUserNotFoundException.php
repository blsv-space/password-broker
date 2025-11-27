<?php

namespace App\Module\Identity\Application\User\Service\Exception;

use JetBrains\PhpStorm\Pure;

class AuthUserNotFoundException extends AuthException
{
    #[Pure]
    public function __construct(string $username)
    {
        parent::__construct('User not found: ' . $username);
    }

}