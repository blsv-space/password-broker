<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryGroup\DTO;

use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\Repository\EntryGroupRepository;
use Inquisition\Core\Application\DTO\EntityResponseInterface;
use Inquisition\Core\Domain\Entity\EntityInterface;
use InvalidArgumentException;

class EntryGroupResponse implements EntityResponseInterface
{
    private EntryGroup $entryGroup;

    /**
     * @throws InvalidArgumentException
     */
    #[\Override]
    public static function fromEntity(EntityInterface $entity): static
    {
        if (!$entity instanceof EntryGroup) {
            throw new InvalidArgumentException('Invalid entity type');
        }

        $entryGroupResponse = new static();
        $entryGroupResponse->entryGroup = $entity;

        return $entryGroupResponse;
    }

    #[\Override]
    public function getAsArray(): array
    {
        return [
            EntryGroupRepository::FIELD_ID => $this->entryGroup->id->toRaw(),
            EntryGroupRepository::FIELD_PARENT_ENTRY_GROUP_ID => $this->entryGroup->parentEntryGroupId?->toRaw(),
            EntryGroupRepository::FIELD_NAME => $this->entryGroup->name->toRaw(),
            EntryGroupRepository::FIELD_MATERIALIZED_PATH => $this->entryGroup->materializedPath->toRaw(),
            EntryGroupRepository::FIELD_CREATED_AT => $this->entryGroup->createdAt?->toRaw(),
            EntryGroupRepository::FIELD_UPDATED_AT => $this->entryGroup->updatedAt?->toRaw(),
            EntryGroupRepository::FIELD_DELETED_AT => $this->entryGroup->deletedAt?->toRaw(),
        ];
    }
}
