<?php

declare(strict_types=1);

namespace App\Module\Identity\Domain\User\Repository;

use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\ValueObject\UserName;
use App\Shared\Infrastructure\Repository\EntityRepositoryInterface;
use Inquisition\Core\Domain\Entity\EntityInterface;
use Inquisition\Core\Domain\Repository\RepositoryInterface;

/**
 * @template TEntity of EntityInterface
 * @extends RepositoryInterface<TEntity>
 * @extends EntityRepositoryInterface<TEntity>
 */
interface UserRepositoryInterface extends RepositoryInterface, EntityRepositoryInterface
{
    public function findByUserName(UserName $userName): ?User;
}
