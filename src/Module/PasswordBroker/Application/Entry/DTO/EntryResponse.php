<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\Entry\DTO;

use App\Module\PasswordBroker\Domain\Entry\Entity\Entry;
use App\Module\PasswordBroker\Infrastructure\Entry\Repository\EntryRepository;
use Inquisition\Core\Application\DTO\EntityResponseInterface;
use Inquisition\Core\Domain\Entity\EntityInterface;
use InvalidArgumentException;

class EntryResponse implements EntityResponseInterface
{
    public const string RESPONSE_NAME = 'entry';
    public const string RESPONSE_MANY_NAME = 'entries';

    private Entry $entry;

    /**
     * @throws InvalidArgumentException
     */
    #[\Override]
    public static function fromEntity(EntityInterface $entity): static
    {
        if (!$entity instanceof Entry) {
            throw new InvalidArgumentException('Invalid entity type');
        }

        $entryGroupResponse = new static();
        $entryGroupResponse->entry = $entity;

        return $entryGroupResponse;
    }

    #[\Override]
    public function getAsArray(): array
    {
        return [
            EntryRepository::FIELD_ID => $this->entry->id->toRaw(),
            EntryRepository::FIELD_ENTRY_GROUP_ID => $this->entry->entryGroupId->toRaw(),
            EntryRepository::FIELD_TITLE => $this->entry->title->toRaw(),
            EntryRepository::FIELD_CREATED_AT => $this->entry->createdAt?->toRaw(),
            EntryRepository::FIELD_UPDATED_AT => $this->entry->updatedAt?->toRaw(),
            EntryRepository::FIELD_DELETED_AT => $this->entry->deletedAt?->toRaw(),
        ];
    }
}
