<?php

declare(strict_types=1);

namespace App\Module\Identity\Domain\RefreshToken\Service;

use App\Module\Identity\Domain\RefreshToken\Entity\RefreshToken;
use App\Module\Identity\Domain\RefreshToken\Service\Exception\RefreshTokenDomainException;
use DateTimeImmutable;
use Inquisition\Core\Domain\Service\DomainServiceInterface;
use Inquisition\Foundation\Singleton\SingletonTrait;

final class RefreshTokenDomainService implements DomainServiceInterface
{
    use SingletonTrait;


    /**
     * @throws RefreshTokenDomainException
     */
    public function refreshTokenValidate(RefreshToken $refreshToken): void
    {
        if ($refreshToken->expirationAt->value < new DateTimeImmutable()) {
            throw new RefreshTokenDomainException('Refresh token expired');
        }
    }
}
