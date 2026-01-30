<?php

namespace App\Module\PasswordBroker\Application\EntryGroup\DTO;

use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\EntryGroupRepository;
use Inquisition\Core\Application\DTO\EntityResponseInterface;
use Inquisition\Core\Domain\Entity\EntityInterface;
use InvalidArgumentException;

class EntryGroupResponse implements EntityResponseInterface
{
    private EntryGroup $entryGroup;

    /**
     * @param EntityInterface $entity
     * @return static
     *
     * @throws InvalidArgumentException
     */
    public static function fromEntity(EntityInterface $entity): static
    {
        if (!$entity instanceof EntryGroup) {
            throw new InvalidArgumentException('Invalid entity type');
        }

        $entryGroupResponse = new static();
        $entryGroupResponse->entryGroup = $entity;

        return $entryGroupResponse;
    }

    /**
     * @return array
     */
    public function getAsArray(): array
    {
        return [
          EntryGroupRepository::FIELD_ID => $this->entryGroup->id?->value ?? null,
          EntryGroupRepository::FIELD_PARENT_ENTRY_GROUP_ID => $this->entryGroup->parentEntryGroupId?->value ?? null,
          EntryGroupRepository::FIELD_NAME => $this->entryGroup->entryGroupName->toRaw(),
          EntryGroupRepository::FIELD_MATERIALIZED_PATH => $this->entryGroup->materializedPath->toRaw(),
          EntryGroupRepository::FIELD_CREATED_AT => $this->entryGroup?->createdAt->toRaw() ?? null,
          EntryGroupRepository::FIELD_UPDATED_AT => $this->entryGroup?->updatedAt->toRaw() ?? null,
          EntryGroupRepository::FIELD_DELETED_AT => $this->entryGroup?->deletedAt->toRaw() ?? null,
        ];
    }
}