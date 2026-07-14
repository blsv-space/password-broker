<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\DTO\EntryFieldResponse;

use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldFile;
use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use Override;

/**
 * @extends AbstractEntryFieldResponse<EntryFieldFile>
 */
class EntryFieldFileResponse extends AbstractEntryFieldResponse
{
    #[Override]
    public function getAsArray(): array
    {
        $out = $this->getAsArrayGeneral();
        $out[EntryFieldRepository::FIELD_FILE_MIME] = $this->entryField->fileMime->toRaw();
        $out[EntryFieldRepository::FIELD_FILE_NAME] = $this->entryField->fileName->toRaw();
        $out[EntryFieldRepository::FIELD_FILE_SIZE] = $this->entryField->fileSize->toRaw();
        return $out;
    }
}
