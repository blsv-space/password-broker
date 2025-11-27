<?php

namespace App\Shared\Domain\Service;

use App\Shared\Infrastructure\Security\Exception\JwtException;
use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;
use App\Shared\Infrastructure\Security\Exception\JwtTokenExpiredException;
use App\Shared\Infrastructure\Security\JwtAlgoEnum;
use DateInterval;
use Inquisition\Core\Domain\Service\DomainServiceInterface;

interface JwtTokenGeneratorInterface extends DomainServiceInterface
{
    /**
     * @param string $secret
     * @param array|null $payload
     * @param DateInterval|null $ttl
     * @param JwtAlgoEnum|null $algoEnum
     *
     * @return string
     */
    public function generate(string $secret, ?array $payload, ?DateInterval $ttl, ?JwtAlgoEnum $algoEnum): string;

    /**
     * @param string $token
     * @param string $secret
     *
     * @return array{payload: array, header: array}
     *
     * @throws JwtException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     */
    public function verify(string $token, string $secret): array;
}