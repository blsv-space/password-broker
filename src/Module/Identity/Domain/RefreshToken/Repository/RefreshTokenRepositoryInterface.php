<?php

declare(strict_types=1);

namespace App\Module\Identity\Domain\RefreshToken\Repository;

use App\Module\Identity\Domain\RefreshToken\Entity\RefreshToken;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use Inquisition\Core\Domain\Entity\EntityInterface;
use Inquisition\Core\Domain\Repository\RepositoryInterface;

/**
 * @template TEntity of EntityInterface
 * @extends RepositoryInterface<TEntity>
 */
interface RefreshTokenRepositoryInterface extends RepositoryInterface
{
    public function findByUserId(UserId $userId): ?RefreshToken;

    public function cleanExpiredTokens(): void;
}
