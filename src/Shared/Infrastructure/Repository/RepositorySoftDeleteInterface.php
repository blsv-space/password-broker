<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Repository;

use App\Shared\Domain\Entity\EntitySoftDeleteInterface;

/**
 * @template T of EntitySoftDeleteInterface
 */
interface RepositorySoftDeleteInterface
{
    /**
     * @param T $entity
     */
    public function softDelete(EntitySoftDeleteInterface $entity): void;

    /**
     * @param T $entity
     */
    public function restore(EntitySoftDeleteInterface $entity): void;
}
