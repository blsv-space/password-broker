<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\DTO\EntryFieldHistoryResponse;

final readonly class DecryptedResponse
{
    public const string FIELD_ENTRY_FIELD_HISTORY_ID = 'entryFieldHistoryId';
    public const string FIELD_DECRYPTED_VALUE = 'decryptedValue';

    public function __construct(
        public string $entryFieldHistoryId,
        public string $decryptedValue,
    ) {}

    public function getAsArray(): array
    {
        return [
            self::FIELD_ENTRY_FIELD_HISTORY_ID => $this->entryFieldHistoryId,
            self::FIELD_DECRYPTED_VALUE => $this->decryptedValue,
        ];
    }
}
