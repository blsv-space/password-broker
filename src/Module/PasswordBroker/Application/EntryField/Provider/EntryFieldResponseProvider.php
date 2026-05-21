<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Provider;

use App\Module\PasswordBroker\Application\EntryField\DTO\EntryFieldResponse\AbstractEntryFieldResponse;
use App\Module\PasswordBroker\Application\EntryField\DTO\EntryFieldResponse\EntryFieldFileResponse;
use App\Module\PasswordBroker\Application\EntryField\DTO\EntryFieldResponse\EntryFieldLinkResponse;
use App\Module\PasswordBroker\Application\EntryField\DTO\EntryFieldResponse\EntryFieldNoteResponse;
use App\Module\PasswordBroker\Application\EntryField\DTO\EntryFieldResponse\EntryFieldPasswordResponse;
use App\Module\PasswordBroker\Application\EntryField\DTO\EntryFieldResponse\EntryFieldTotpResponse;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use Inquisition\Core\Application\Service\ApplicationServiceInterface;
use Inquisition\Foundation\Singleton\SingletonTrait;
use InvalidArgumentException;

final readonly class EntryFieldResponseProvider implements ApplicationServiceInterface
{
    use SingletonTrait;

    public function response(AbstractEntryField $entryField): AbstractEntryFieldResponse
    {
        return match ($entryField->type->toRaw()) {
            EntryFieldTypeEnum::FILE->value => EntryFieldFileResponse::fromEntity($entryField),
            EntryFieldTypeEnum::LINK->value => EntryFieldLinkResponse::fromEntity($entryField),
            EntryFieldTypeEnum::NOTE->value => EntryFieldNoteResponse::fromEntity($entryField),
            EntryFieldTypeEnum::PASSWORD->value => EntryFieldPasswordResponse::fromEntity($entryField),
            EntryFieldTypeEnum::TOTP->value => EntryFieldTotpResponse::fromEntity($entryField),
            default => throw new InvalidArgumentException('Unknown entry field type'),
        };
    }
}
