<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\DTO\EntryFieldResponse;

use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use Inquisition\Core\Application\DTO\EntityResponseInterface;
use Inquisition\Core\Domain\Entity\EntityInterface;
use InvalidArgumentException;

final class EncryptedValueEntryFieldResponse implements EntityResponseInterface
{
    public const string RESPONSE_NAME = 'encryptedValueEntryField';
    public const string RESPONSE_MANY_NAME = 'encryptedValueEntryFields';

    private AbstractEntryField $entryField;

    /**
     * @throws InvalidArgumentException
     */
    #[\Override]
    public static function fromEntity(EntityInterface $entity): static
    {
        if (!$entity instanceof AbstractEntryField) {
            throw new InvalidArgumentException('Invalid entity type');
        }

        $entryResponse = new static();
        $entryResponse->entryField = $entity;

        return $entryResponse;
    }

    #[\Override]
    public function getAsArray(): array
    {
        return [
            EntryFieldRepository::FIELD_ID => $this->entryField->id->toRaw(),
            EntryFieldRepository::FIELD_VALUE_ENCRYPTED => $this->entryField->valueEncrypted->toRaw(),
            EntryFieldRepository::FIELD_INITIALIZATION_VECTOR => $this->entryField->initializationVector->toRaw(),
            EntryFieldRepository::FIELD_TAG => $this->entryField->tag->toRaw(),
        ];
    }
}
