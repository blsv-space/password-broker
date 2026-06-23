<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\DTO\EntryFieldHistoryResponse;

use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\EntryFieldHistoryPassword;
use App\Module\PasswordBroker\Infrastructure\EntryFieldHistory\Repository\EntryFieldHistoryRepository;
use Override;

/**
 * @extends AbstractEntryFieldHistoryResponse<EntryFieldHistoryPassword>
 */
class EntryFieldHistoryPasswordResponse extends AbstractEntryFieldHistoryResponse
{
    #[Override]
    public function getAsArray(): array
    {
        $out = $this->getAsArrayGeneral();
        $out[EntryFieldHistoryRepository::FIELD_LOGIN] = $this->entryFieldHistory->login->toRaw();
        return $out;
    }
}
