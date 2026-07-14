<?php

declare(strict_types=1);

namespace App\Module\Identity\Infrastructure\Repository;

use Inquisition\Core\Domain\Entity\EntityInterface;
use Inquisition\Core\Infrastructure\Persistence\Repository\AbstractRepository;

/**
 * @template TEntity of EntityInterface
 * @extends  AbstractRepository<TEntity>
 */
abstract class AbstractIdentityRepository extends AbstractRepository
{
    protected const string TABLE_NAME_PREFIX = 'identity';
}
