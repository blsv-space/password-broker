<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryField\Enum;

enum EntryFieldTypeEnum: string
{
    case PASSWORD = 'password';
    case LINK = 'link';
    case NOTE = 'note';
    case FILE = 'file';
    case TOTP = 'totp';

}
