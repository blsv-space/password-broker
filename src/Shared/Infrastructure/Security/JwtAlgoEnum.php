<?php

namespace App\Shared\Infrastructure\Security;

use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;

enum JwtAlgoEnum: string
{
    case HS256 = 'sha256';
    case HS384 = 'sha384';
    case HS512 = 'sha512';

    /**
     * @return self
     */
    static public function default(): self
    {
        return self::HS256;
    }

    /**
     * @param string $name
     * @return JwtAlgoEnum
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
