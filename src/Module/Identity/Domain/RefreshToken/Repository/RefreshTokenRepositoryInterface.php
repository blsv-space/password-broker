<?php

namespace App\Module\Identity\Domain\RefreshToken\Repository;

use App\Module\Identity\Domain\RefreshToken\Entity\RefreshToken;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use Inquisition\Core\Domain\Repository\RepositoryInterface;

interface RefreshTokenRepositoryInterface extends RepositoryInterface
{
    public function findByUserId(UserId $userId): ?RefreshToken;

    public function cleanExpiredTokens(): void;
}