<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryField\Enum;

use App\Shared\Domain\ValueObject\EnumToArrayInterface;
use Override;

enum EntryFieldTypeEnum: string implements EnumToArrayInterface
{
    case PASSWORD = 'password';
    case LINK = 'link';
    case NOTE = 'note';
    case FILE = 'file';
    case TOTP = 'totp';

    #[Override]
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
