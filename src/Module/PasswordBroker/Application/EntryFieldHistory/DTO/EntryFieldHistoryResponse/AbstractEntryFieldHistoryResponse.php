<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\DTO\EntryFieldHistoryResponse;

use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\AbstractEntryFieldHistory;
use App\Module\PasswordBroker\Infrastructure\EntryFieldHistory\Repository\EntryFieldHistoryRepository;
use Inquisition\Core\Application\DTO\EntityResponseInterface;
use Inquisition\Core\Domain\Entity\EntityInterface;
use InvalidArgumentException;
use Override;

/**
 * @template T of AbstractEntryFieldHistory
 */
abstract class AbstractEntryFieldHistoryResponse implements EntityResponseInterface
{
    public const string RESPONSE_NAME = 'entryFieldHistory';
    public const string RESPONSE_MANY_NAME = 'entryFieldHistories';

    /**
     * @psalm-var T
     */
    protected AbstractEntryFieldHistory $entryFieldHistory {
        get {
            return $this->entryFieldHistory;
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Override]
    public static function fromEntity(EntityInterface $entity): static
    {
        if (!$entity instanceof AbstractEntryFieldHistory) {
            throw new InvalidArgumentException('Invalid entity type');
        }

        $entryFieldHistoryResponse = new static();
        $entryFieldHistoryResponse->entryFieldHistory = $entity;

        return $entryFieldHistoryResponse;
    }

    protected function getAsArrayGeneral(): array
    {
        return [
            EntryFieldHistoryRepository::FIELD_ID => $this->entryFieldHistory->id->toRaw(),
            EntryFieldHistoryRepository::FIELD_ENTRY_FIELD_ID => $this->entryFieldHistory->entryFieldId->toRaw(),
            EntryFieldHistoryRepository::FIELD_EVENT_NAME => $this->entryFieldHistory->eventName->toRaw(),
            EntryFieldHistoryRepository::FIELD_TITLE => $this->entryFieldHistory->title->toRaw(),
            EntryFieldHistoryRepository::FIELD_TYPE => $this->entryFieldHistory->type->value,
            EntryFieldHistoryRepository::FIELD_IS_DELETED => $this->entryFieldHistory->isDeleted->toRaw(),
            EntryFieldHistoryRepository::FIELD_CREATED_AT => $this->entryFieldHistory->createdAt->toRaw(),
            EntryFieldHistoryRepository::FIELD_CREATED_BY => $this->entryFieldHistory->createdBy->toRaw(),
        ];
    }
}
