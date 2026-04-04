<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryGroupUser\Enum;

enum RoleEnum: string
{
    case ADMIN = 'admin';
    case MEMBER = 'member';
    case MODERATOR = 'moderator';

    public function default(): bool
    {
        return $this === self::MEMBER;
    }
}
