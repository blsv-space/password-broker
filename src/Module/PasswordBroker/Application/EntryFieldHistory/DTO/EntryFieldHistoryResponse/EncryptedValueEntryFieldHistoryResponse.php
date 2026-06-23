<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\DTO\EntryFieldHistoryResponse;

use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\AbstractEntryFieldHistory;
use App\Module\PasswordBroker\Infrastructure\EntryFieldHistory\Repository\EntryFieldHistoryRepository;
use Inquisition\Core\Application\DTO\EntityResponseInterface;
use Inquisition\Core\Domain\Entity\EntityInterface;
use InvalidArgumentException;

final class EncryptedValueEntryFieldHistoryResponse implements EntityResponseInterface
{
    public const string RESPONSE_NAME = 'encryptedValueEntryFieldHistory';
    public const string RESPONSE_MANY_NAME = 'encryptedValueEntryHistoryFields';

    private AbstractEntryFieldHistory $entryFieldHistory;

    /**
     * @throws InvalidArgumentException
     */
    #[\Override]
    public static function fromEntity(EntityInterface $entity): static
    {
        if (!$entity instanceof AbstractEntryFieldHistory) {
            throw new InvalidArgumentException('Invalid entity type');
        }

        $entryFieldHistoryResponse = new static();
        $entryFieldHistoryResponse->entryFieldHistory = $entity;

        return $entryFieldHistoryResponse;
    }

    #[\Override]
    public function getAsArray(): array
    {
        return [
            EntryFieldHistoryRepository::FIELD_ID => $this->entryFieldHistory->id->toRaw(),
            EntryFieldHistoryRepository::FIELD_VALUE_ENCRYPTED => $this->entryFieldHistory->valueEncrypted->toRaw(),
            EntryFieldHistoryRepository::FIELD_INITIALIZATION_VECTOR => $this->entryFieldHistory->initializationVector->toRaw(),
            EntryFieldHistoryRepository::FIELD_TAG => $this->entryFieldHistory->tag->toRaw(),
        ];
    }
}
