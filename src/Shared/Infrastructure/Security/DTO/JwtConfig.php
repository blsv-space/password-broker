<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security\DTO;

use App\Shared\Infrastructure\Security\JwtAlgoEnum;
use DateInterval;

final readonly class JwtConfig
{
    public function __construct(
        public string $secret,
        public DateInterval $ttl,
        public JwtAlgoEnum $algorithm,
    ) {}
}
