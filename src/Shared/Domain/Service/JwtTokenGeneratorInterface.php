<?php

declare(strict_types=1);

namespace App\Shared\Domain\Service;

use App\Shared\Infrastructure\Security\DTO\JwtConfig;
use App\Shared\Infrastructure\Security\Exception\JwtException;
use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;
use App\Shared\Infrastructure\Security\Exception\JwtTokenExpiredException;
use App\Shared\Infrastructure\Security\JwtAlgoEnum;
use DateInterval;
use Inquisition\Core\Domain\Service\DomainServiceInterface;

interface JwtTokenGeneratorInterface extends DomainServiceInterface
{
    public function generateByJwtConfig(JwtConfig $jwtConfig, ?array $payload): string;

    public function generate(string $secret, ?array $payload, ?DateInterval $ttl, ?JwtAlgoEnum $algoEnum): string;

    /**
     *
     *
     * @throws JwtException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @return array{payload: array, header: array}
     */
    public function verify(string $token, string $secret): array;
}
