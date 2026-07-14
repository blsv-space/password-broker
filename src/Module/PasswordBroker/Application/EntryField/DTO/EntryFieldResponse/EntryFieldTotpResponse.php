<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\DTO\EntryFieldResponse;

use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldTotp;
use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use Override;

/**
 * @extends AbstractEntryFieldResponse<EntryFieldTotp>
 */
class EntryFieldTotpResponse extends AbstractEntryFieldResponse
{
    #[Override]
    public function getAsArray(): array
    {
        $out = $this->getAsArrayGeneral();
        $out[EntryFieldRepository::FIELD_TOTP_HASH_ALGORITHM] = $this->entryField->totpHashAlgorithm->toRaw();
        $out[EntryFieldRepository::FIELD_TOTP_TIMEOUT] = $this->entryField->totpTimeout->toRaw();

        return $out;
    }
}
