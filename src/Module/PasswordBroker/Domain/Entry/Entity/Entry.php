<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\Entry\Entity;

use App\Module\PasswordBroker\Domain\Entry\ValueObject\EntryId;
use App\Module\PasswordBroker\Domain\Entry\ValueObject\Title;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Shared\Domain\Entity\EntitySoftDeleteInterface;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\DeletedAt;
use App\Shared\Domain\ValueObject\UpdatedAt;
use Inquisition\Core\Domain\Entity\BaseEntityWithId;
use Inquisition\Core\Domain\Entity\EntityInterface;
use Inquisition\Core\Domain\ValueObject\ValueObjectInterface;

class Entry extends BaseEntityWithId implements EntityInterface, EntitySoftDeleteInterface
{
    public function __construct(
        public EntryId     $id {
            get {
                return $this->id;
            }
        },
        public EntryGroupId $entryGroupId,
        public Title        $title,
        public ?CreatedAt   $createdAt,
        public ?UpdatedAt   $updatedAt,
        public ?DeletedAt   $deletedAt,
    )
    {}

    /**
     * @return EntryId|null
     */
    #[\Override]
    public function getId(): ?ValueObjectInterface
    {
        return $this->id;
    }
}
