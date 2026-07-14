<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryGroupUser\DTO;

use App\Module\PasswordBroker\Domain\EntryGroupUser\Entity\EntryGroupUser;
use App\Module\PasswordBroker\Infrastructure\EntryGroupUser\Repository\EntryGroupUserRepository;
use Inquisition\Core\Application\DTO\EntityResponseInterface;
use Inquisition\Core\Domain\Entity\EntityInterface;
use InvalidArgumentException;

class EntryGroupUserResponse implements EntityResponseInterface
{
    private EntryGroupUser $entryGroupUser;

    #[\Override]
    public static function fromEntity(EntityInterface $entity): static
    {
        if (!$entity instanceof EntryGroupUser) {
            throw new InvalidArgumentException('Invalid entity type');
        }

        $entryGroupResponse = new static();
        $entryGroupResponse->entryGroupUser = $entity;

        return $entryGroupResponse;
    }

    #[\Override]
    public function getAsArray(): array
    {
        return [
            EntryGroupUserRepository::FIELD_ID => $this->entryGroupUser->id->toRaw(),
            EntryGroupUserRepository::FIELD_ENTRY_GROUP_ID => $this->entryGroupUser->entryGroupId->toRaw(),
            EntryGroupUserRepository::FIELD_USER_ID => $this->entryGroupUser->userId->toRaw(),
            EntryGroupUserRepository::FIELD_ROLE => $this->entryGroupUser->role->toRaw(),
            EntryGroupUserRepository::FIELD_CREATED_AT => $this->entryGroupUser->createdAt?->toRaw(),
            EntryGroupUserRepository::FIELD_UPDATED_AT => $this->entryGroupUser->updatedAt?->toRaw(),
        ];
    }
}
