<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\DTO\EntryFieldResponse;

use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldPassword;
use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use Override;

/**
 * @extends AbstractEntryFieldResponse<EntryFieldPassword>
 */
class EntryFieldPasswordResponse extends AbstractEntryFieldResponse
{
    #[Override]
    public function getAsArray(): array
    {
        $out = $this->getAsArrayGeneral();
        $out[EntryFieldRepository::FIELD_LOGIN] = $this->entryField->login->toRaw();
        return $out;
    }
}
