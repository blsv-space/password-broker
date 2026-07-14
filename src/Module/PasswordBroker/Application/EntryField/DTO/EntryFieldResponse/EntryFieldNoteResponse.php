<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\DTO\EntryFieldResponse;

use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldNote;
use Override;

/**
 * @extends AbstractEntryFieldResponse<EntryFieldNote>
 */
class EntryFieldNoteResponse extends AbstractEntryFieldResponse
{
    #[Override]
    public function getAsArray(): array
    {
        return $this->getAsArrayGeneral();
    }
}
