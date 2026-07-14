<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security\Exception;

use Throwable;

class JwtTokenExpiredException extends JwtException
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        if (empty($message)) {
            $message = 'Token expired';
        }
        parent::__construct($message, $code, $previous);
    }

}
