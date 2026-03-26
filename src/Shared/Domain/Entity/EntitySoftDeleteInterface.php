<?php

declare(strict_types=1);

namespace App\Shared\Domain\Entity;

use App\Shared\Domain\ValueObject\DeletedAt;
use Inquisition\Core\Domain\Entity\EntityWithIdInterface;

interface EntitySoftDeleteInterface extends EntityWithIdInterface
{
    public ?DeletedAt     $deletedAt {
        get;
        set;
    }
}
