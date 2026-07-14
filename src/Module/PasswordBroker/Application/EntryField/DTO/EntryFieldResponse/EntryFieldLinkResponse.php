<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\DTO\EntryFieldResponse;

use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldLink;
use Override;

/**
 * @extends AbstractEntryFieldResponse<EntryFieldLink>
 */
class EntryFieldLinkResponse extends AbstractEntryFieldResponse
{
    #[Override]
    public function getAsArray(): array
    {
        return $this->getAsArrayGeneral();
    }
}
