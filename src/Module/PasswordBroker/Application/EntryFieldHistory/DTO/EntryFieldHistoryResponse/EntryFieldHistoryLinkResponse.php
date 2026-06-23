<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\DTO\EntryFieldHistoryResponse;

use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\EntryFieldHistoryLink;
use Override;

/**
 * @extends AbstractEntryFieldHistoryResponse<EntryFieldHistoryLink>
 */
class EntryFieldHistoryLinkResponse extends AbstractEntryFieldHistoryResponse
{
    #[Override]
    public function getAsArray(): array
    {
        return $this->getAsArrayGeneral();
    }
}
