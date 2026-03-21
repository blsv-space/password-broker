<?php

declare(strict_types=1);

namespace App\Module\Identity\Application\User\Service\Exception;

use JetBrains\PhpStorm\Pure;

class AuthUserNotFoundException extends AuthException
{
    public function __construct(string $username)
    {
        parent::__construct('User not found: ' . $username);
    }

}
