<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\Provider;

use App\Module\PasswordBroker\Application\EntryFieldHistory\DTO\EntryFieldHistoryResponse\AbstractEntryFieldHistoryResponse;
use App\Module\PasswordBroker\Application\EntryFieldHistory\DTO\EntryFieldHistoryResponse\EntryFieldHistoryLinkResponse;
use App\Module\PasswordBroker\Application\EntryFieldHistory\DTO\EntryFieldHistoryResponse\EntryFieldHistoryNoteResponse;
use App\Module\PasswordBroker\Application\EntryFieldHistory\DTO\EntryFieldHistoryResponse\EntryFieldHistoryPasswordResponse;
use App\Module\PasswordBroker\Application\EntryFieldHistory\DTO\EntryFieldHistoryResponse\EntryFieldHistoryTotpResponse;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\AbstractEntryFieldHistory;
use Inquisition\Core\Application\Service\ApplicationServiceInterface;
use Inquisition\Foundation\Singleton\SingletonTrait;
use InvalidArgumentException;

final class EntryFieldHistoryResponseProvider implements ApplicationServiceInterface
{
    use SingletonTrait;

    public function response(AbstractEntryFieldHistory $entryFieldHistory): AbstractEntryFieldHistoryResponse
    {
        return match ($entryFieldHistory->type->toRaw()) {
            EntryFieldTypeEnum::FILE->value => throw new InvalidArgumentException('Unsupported field type FILE'),
            EntryFieldTypeEnum::LINK->value => EntryFieldHistoryLinkResponse::fromEntity($entryFieldHistory),
            EntryFieldTypeEnum::NOTE->value => EntryFieldHistoryNoteResponse::fromEntity($entryFieldHistory),
            EntryFieldTypeEnum::PASSWORD->value => EntryFieldHistoryPasswordResponse::fromEntity($entryFieldHistory),
            EntryFieldTypeEnum::TOTP->value => EntryFieldHistoryTotpResponse::fromEntity($entryFieldHistory),
            default => throw new InvalidArgumentException('Unknown entry field type'),
        };
    }
}
