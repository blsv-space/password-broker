<?php

declare(strict_types=1);

namespace App\Module\Identity\Domain\User\Entity;

use App\Module\Identity\Domain\User\ValueObject\Email;
use App\Module\Identity\Domain\User\ValueObject\HashedPassword;
use App\Module\Identity\Domain\User\ValueObject\IsAdmin;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\Identity\Domain\User\ValueObject\UserName;
use App\Module\Identity\Domain\User\ValueObject\UserPublicKey;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\UpdatedAt;
use Inquisition\Core\Domain\Entity\BaseEntityWithId;
use Inquisition\Core\Domain\Entity\EntityInterface;
use Inquisition\Core\Domain\ValueObject\ValueObjectInterface;

class User extends BaseEntityWithId implements EntityInterface
{
    public function __construct(
        public UserId        $id {
            get {
                return $this->id;
            }
        },
        public UserName       $userName,
        public HashedPassword $hashedPassword,
        public IsAdmin        $isAdmin,
        public Email          $email,
        public UserPublicKey  $publicKey,
        public ?CreatedAt     $createdAt = null,
        public ?UpdatedAt     $updatedAt = null,
    )
    {
    }

    /**
     * @return UserId|null
     */
    #[\Override]
    public function getId(): ?ValueObjectInterface
    {
        return $this->id;
    }

}
