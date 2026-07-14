<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use App\Shared\Infrastructure\Security\DTO\JwtConfig;
use DateInterval;
use Exception;
use Inquisition\Foundation\Config\Config;
use Inquisition\Foundation\Singleton\SingletonInterface;
use Inquisition\Foundation\Singleton\SingletonTrait;
use RuntimeException;

class JwtConfigProvider implements SingletonInterface
{
    use SingletonTrait;

    private const string JWT_TTL_DEFAULT = '1 day';

    private Config $config;

    private function __construct()
    {
        $this->config = Config::getInstance();
    }

    public function getSecret(): mixed
    {
        $jwtSecret = $this->config->getByPath('security.jwt.secret');
        if (empty($jwtSecret)) {
            throw new RuntimeException('JWT secret is not set in security.jwt.secret.');
        }

        return $jwtSecret;
    }

    public function getTtl(): DateInterval
    {
        try {
            $jwtTtl = DateInterval::createFromDateString($this->config->getByPath('security.jwt.time_to_live', self::JWT_TTL_DEFAULT));
            if ($jwtTtl === false) {
                throw new Exception();
            }
        } catch (Exception $_) {
            throw new RuntimeException('Invalid time to live format. Should be set in config in security.jwt.time_to_live.');
        }

        return $jwtTtl;
    }

    public function getAlgorithm(): JwtAlgoEnum
    {
        $jwtAlgorithm = $this->config->getByPath('security.jwt.algo', 'sha256');
        $jwtAlgoEnum = JwtAlgoEnum::tryFrom($jwtAlgorithm);
        if (!$jwtAlgoEnum) {
            throw new RuntimeException('Invalid JWT algorithm. Should be set in config in security.jwt.algo.');
        }

        return $jwtAlgoEnum;
    }

    /**
     * Get all JWT configuration at once
     */
    public function getConfig(): JwtConfig
    {
        return new JwtConfig(
            secret: $this->getSecret(),
            ttl: $this->getTtl(),
            algorithm: $this->getAlgorithm(),
        );
    }
}
