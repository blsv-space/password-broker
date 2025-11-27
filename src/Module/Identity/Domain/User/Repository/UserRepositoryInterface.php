<?php

namespace App\Module\Identity\Domain\User\Repository;

use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\ValueObject\UserName;
use Inquisition\Core\Domain\Repository\RepositoryInterface;

interface UserRepositoryInterface extends RepositoryInterface
{
    public function findByUserName(UserName $userName): ?User;
}