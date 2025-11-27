<?php

namespace App\Module\Identity\Domain\User\Entity;

use App\Module\Identity\Domain\User\ValueObject\HashedPassword;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\Identity\Domain\User\ValueObject\UserName;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\UpdatedAt;
use Inquisition\Core\Domain\Entity\BaseEntityWithId;
use Inquisition\Core\Domain\Entity\EntityInterface;
use Inquisition\Core\Domain\ValueObject\ValueObjectInterface;

class User extends BaseEntityWithId
    implements EntityInterface
{
    public function __construct(
        public UserName       $userName,
        public HashedPassword $hashedPassword,
        public ?UserId        $id = null {
            get {
                return $this->id;
            }
        },
        public ?CreatedAt     $createdAt = null,
        public ?UpdatedAt     $updatedAt = null,
    )
    {
    }

    /**
     * @return UserId|null
     */
    public function getId(): ?ValueObjectInterface
    {
        return $this->id;
    }

}