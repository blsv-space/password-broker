<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Repository;

/**
 * @template T
 */
interface EntityRepositoryInterface
{
    /**
     * Maps an array to an entity.
     *
     * @return T
     */
    public function mapArrayToEntity(array $array);
}
