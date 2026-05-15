<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryField\Entity;

use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\PasswordBroker\Domain\Entry\ValueObject\EntryId;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldId;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldInitializationVector;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTag;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTitle;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldType;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldValueEncrypted;
use App\Shared\Domain\Entity\EntitySoftDeleteInterface;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\DeletedAt;
use App\Shared\Domain\ValueObject\UpdatedAt;
use Inquisition\Core\Domain\Entity\BaseEntityWithId;
use Inquisition\Core\Domain\Entity\EntityInterface;
use Inquisition\Core\Domain\ValueObject\ValueObjectInterface;

abstract class EntryField extends BaseEntityWithId implements EntityInterface, EntitySoftDeleteInterface
{
    protected function __construct(
        public EntryFieldId $id {
            get {
                return $this->id;
            }
        },
        public readonly EntryId               $entryId,
        public readonly EntryFieldType        $type,
        public EntryFieldTitle                $title,
        public EntryFieldValueEncrypted       $valueEncrypted,
        public EntryFieldInitializationVector $initializationVector,
        public EntryFieldTag                  $tag,
        public ?CreatedAt                     $createdAt,
        public ?UpdatedAt                     $updatedAt,
        public ?DeletedAt                     $deletedAt,
        public UserId                         $createdBy,
        public ?UserId                        $updatedBy,
    ) {}

    /**
     * @return EntryFieldId|null
     */
    #[\Override]
    public function getId(): ?ValueObjectInterface
    {
        return $this->id;
    }
}
