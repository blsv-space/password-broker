<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\DTO\EntryFieldResponse;

use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use Inquisition\Core\Application\DTO\EntityResponseInterface;
use Inquisition\Core\Domain\Entity\EntityInterface;
use InvalidArgumentException;
use Override;

/**
 * @template T of AbstractEntryField
 */
abstract class AbstractEntryFieldResponse implements EntityResponseInterface
{
    public const string RESPONSE_NAME = 'entryField';
    public const string RESPONSE_MANY_NAME = 'entryFields';

    /**
     * @psalm-var T
     */
    protected AbstractEntryField $entryField {
        get {
            return $this->entryField;
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Override]
    public static function fromEntity(EntityInterface $entity): static
    {
        if (!$entity instanceof AbstractEntryField) {
            throw new InvalidArgumentException('Invalid entity type');
        }

        $entryFieldResponse = new static();
        $entryFieldResponse->entryField = $entity;

        return $entryFieldResponse;
    }

    protected function getAsArrayGeneral(): array
    {
        return [
            EntryFieldRepository::FIELD_ID => $this->entryField->id->toRaw(),
            EntryFieldRepository::FIELD_ENTRY_ID => $this->entryField->entryId->toRaw(),
            EntryFieldRepository::FIELD_TYPE => $this->entryField->type->value,
            EntryFieldRepository::FIELD_CREATED_AT => $this->entryField->createdAt?->toRaw(),
            EntryFieldRepository::FIELD_UPDATED_AT => $this->entryField->updatedAt?->toRaw(),
            EntryFieldRepository::FIELD_DELETED_AT => $this->entryField->deletedAt?->toRaw(),
            EntryFieldRepository::FIELD_UPDATED_BY => $this->entryField->updatedBy?->toRaw(),
            EntryFieldRepository::FIELD_CREATED_BY => $this->entryField->createdBy->toRaw(),
        ];
    }
}
