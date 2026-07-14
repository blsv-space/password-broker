<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryField\Enum;

use App\Shared\Domain\ValueObject\EnumToArrayInterface;
use Override;

enum EntryFieldTotpHashAlgorithmEnum: string implements EnumToArrayInterface
{
    case SHA1 = 'sha1';
    case SHA256 = 'sha256';
    case SHA512 = 'sha512';

    #[Override]
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
