<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryField\Enum;

enum EntryFieldTotpHashAlgorithmEnum: string
{
    case SHA1 = 'sha1';
    case SHA256 = 'sha256';
    case SHA512 = 'sha512';
}
