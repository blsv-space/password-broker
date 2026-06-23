<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\DTO\EntryFieldHistoryResponse;

use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\EntryFieldHistoryTotp;
use App\Module\PasswordBroker\Infrastructure\EntryFieldHistory\Repository\EntryFieldHistoryRepository;
use Override;

/**
 * @extends AbstractEntryFieldHistoryResponse<EntryFieldHistoryTotp>
 */
class EntryFieldHistoryTotpResponse extends AbstractEntryFieldHistoryResponse
{
    #[Override]
    public function getAsArray(): array
    {
        $out = $this->getAsArrayGeneral();
        $out[EntryFieldHistoryRepository::FIELD_TOTP_HASH_ALGORITHM] = $this->entryFieldHistory->totpHashAlgorithm->toRaw();
        $out[EntryFieldHistoryRepository::FIELD_TOTP_TIMEOUT] = $this->entryFieldHistory->totpTimeout->toRaw();

        return $out;
    }
}
