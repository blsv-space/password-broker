<?php

namespace App\Module\PasswordBroker\Infrastructure\Repository;

use Inquisition\Core\Infrastructure\Persistence\Repository\AbstractRepository;

abstract class AbstractPasswordBrokerRepository extends AbstractRepository
{
    protected const string TABLE_NAME_PREFIX = 'passwordBroker';
}