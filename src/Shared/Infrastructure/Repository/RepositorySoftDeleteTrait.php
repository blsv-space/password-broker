<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Repository;

use App\Shared\Domain\Entity\EntitySoftDeleteInterface;
use App\Shared\Domain\ValueObject\DeletedAt;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\AbstractRepository;

/**
 * @template T of EntitySoftDeleteInterface
 */
trait RepositorySoftDeleteTrait
{
    /**
     * @param  T                    $entity
     * @throws PersistenceException
     */
    public function softDelete(EntitySoftDeleteInterface $entity): void
    {
        if (!is_subclass_of($this, AbstractRepository::class)) {
            throw new PersistenceException('RepositorySoftDeleteTrait should be used only with AbstractRepository child classes');
        }

        if ($entity->deletedAt) {
            return;
        }

        $entity->deletedAt = DeletedAt::now();

        $this->getConnection()->connect()
            ->prepare('UPDATE ' . $this->getTableName() . ' SET `deletedAt` = :deletedAt WHERE `id` = :id')
            ->execute([
                'deletedAt' => $entity->deletedAt->toRaw(),
                'id' => $entity->getId()->toRaw(),
            ]);

    }

    /**
     * @param  T                    $entity
     * @throws PersistenceException
     */
    public function restore(EntitySoftDeleteInterface $entity): void
    {
        if (!is_subclass_of($this, AbstractRepository::class)) {
            throw new PersistenceException('RepositorySoftDeleteTrait should be used only with AbstractRepository child classes');
        }

        if (!$entity->deletedAt) {
            return;
        }

        $entity->deletedAt = null;

        $this->getConnection()->connect()
            ->prepare('UPDATE ' . $this->getTableName() . ' SET `deletedAt` = NULL WHERE `id` = :id')
            ->execute([
                'id' => $entity->getId()->toRaw(),
            ]);

    }
}
