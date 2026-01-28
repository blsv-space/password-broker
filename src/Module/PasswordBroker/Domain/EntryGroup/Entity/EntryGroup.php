<?php

namespace App\Module\PasswordBroker\Domain\EntryGroup\Entity;

use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupName;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\MaterializedPath;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\DeletedAt;
use App\Shared\Domain\ValueObject\UpdatedAt;
use Inquisition\Core\Domain\Entity\BaseEntityWithId;
use Inquisition\Core\Domain\Entity\EntityInterface;
use Inquisition\Core\Domain\ValueObject\ValueObjectInterface;

class EntryGroup extends BaseEntityWithId
    implements EntityInterface
{
    public function __construct(
        public EntryGroupId $id {
            get {
                return $this->id;
            }
        },
        public EntryGroupId $parentEntryGroupId,
        public EntryGroupName $entryGroupName,
        public MaterializedPath $materializedPath,
        public ?CreatedAt $createdAt,
        public ?UpdatedAt $updatedAt,
        public ?DeletedAt $deletedAt,
    )
    {}

    /**
     * @return EntryGroupId|null
     */
    public function getId(): ?ValueObjectInterface
    {
        return $this->id;
    }
}