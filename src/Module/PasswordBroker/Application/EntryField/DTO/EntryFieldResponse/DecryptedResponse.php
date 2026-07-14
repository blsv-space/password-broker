<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\DTO\EntryFieldResponse;

final readonly class DecryptedResponse
{
    public const string FIELD_ENTRY_FIELD_ID = 'entryFieldId';
    public const string FIELD_DECRYPTED_VALUE = 'decryptedValue';

    public function __construct(
        public string $entryFieldId,
        public string $decryptedValue,
    ) {}

    public function getAsArray(): array
    {
        return [
            self::FIELD_ENTRY_FIELD_ID => $this->entryFieldId,
            self::FIELD_DECRYPTED_VALUE => $this->decryptedValue,
        ];
    }
}
