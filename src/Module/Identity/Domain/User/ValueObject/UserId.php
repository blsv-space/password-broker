<?php

namespace App\Module\Identity\Domain\User\ValueObject;

use App\Shared\Domain\ValueObject\Id;
use Inquisition\Core\Domain\ValueObject\ValueObjectInterface;

class UserId extends Id
    implements ValueObjectInterface
{
}