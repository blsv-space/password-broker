<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryGroupUser\Entity;

use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject\EncryptedAesPassword;
use App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject\EntryGroupUserId;
use App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject\Role;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\UpdatedAt;
use Inquisition\Core\Domain\Entity\BaseEntityWithId;
use Inquisition\Core\Domain\Entity\EntityInterface;
use Inquisition\Core\Domain\ValueObject\ValueObjectInterface;

class EntryGroupUser extends BaseEntityWithId implements EntityInterface
{
    public function __construct(
        public EntryGroupUserId     $id {
            get {
                return $this->id;
            }
        },
        public EntryGroupId         $entryGroupId,
        public UserId               $userId,
        public Role                 $role,
        public EncryptedAesPassword $encryptedAesPassword,
        public ?CreatedAt $createdAt,
        public ?UpdatedAt $updatedAt,
    ) {}

    /**
     * @return EntryGroupUserId|null
     */
    #[\Override]
    public function getId(): ?ValueObjectInterface
    {
        return $this->id;
    }
}
