<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryGroupUser\Enum;

use App\Shared\Domain\ValueObject\EnumToArrayInterface;
use Override;

enum RoleEnum: string implements EnumToArrayInterface
{

    case ADMIN = 'admin';
    case MEMBER = 'member';
    case MODERATOR = 'moderator';

    public function default(): bool
    {
        return $this === self::MEMBER;
    }

    #[Override]
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
