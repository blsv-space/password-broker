<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;

enum JwtAlgoEnum: string
{
    case HS256 = 'sha256';
    case HS384 = 'sha384';
    case HS512 = 'sha512';

    public static function default(): self
    {
        return self::HS256;
    }

    /**
     * @throws JwtInvalidTokenException
     */
    public static function getFromJWTIdentifier(string $name): JwtAlgoEnum
    {
        return match ($name) {
            'HS256' => self::HS256,
            'HS384' => self::HS384,
            'HS512' => self::HS512,
            default => throw new JwtInvalidTokenException('Invalid token format. Unsupported algorithm: '
                . $name),
        };
    }
}
