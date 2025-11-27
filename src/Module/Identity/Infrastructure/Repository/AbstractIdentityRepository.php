<?php

namespace App\Module\Identity\Infrastructure\Repository;

use Inquisition\Core\Infrastructure\Persistence\Repository\AbstractRepository;

abstract class AbstractIdentityRepository extends AbstractRepository
{
    protected const string TABLE_NAME_PREFIX = 'identity';
}