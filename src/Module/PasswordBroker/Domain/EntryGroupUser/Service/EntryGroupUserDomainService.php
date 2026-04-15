<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryGroupUser\Service;

use App\Module\PasswordBroker\Domain\EntryGroupUser\Entity\EntryGroupUser;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Enum\RoleEnum;
use App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject\Role;
use Inquisition\Core\Domain\Service\DomainServiceInterface;
use Inquisition\Foundation\Singleton\SingletonTrait;
use JsonException;
use Random\RandomException;
use RuntimeException;

class EntryGroupUserDomainService implements DomainServiceInterface
{
    use SingletonTrait;

    public function canAddUserWithRoleToEntryGroup(Role $role, EntryGroupUser $entryGroupUserAuthUser): bool
    {
        return match ($entryGroupUserAuthUser->role->value) {
            RoleEnum::ADMIN => true,
            RoleEnum::MODERATOR => $role->value === RoleEnum::MEMBER,
            default => false,
        };
    }

    public function canDeleteUserFromEntryGroup(
        EntryGroupUser $entryGroupUserTargetUser,
        EntryGroupUser $entryGroupUserAuthUser,
    ): bool {
        try {
            if ($entryGroupUserTargetUser->userId->equals($entryGroupUserAuthUser->userId)) {
                return true;
            }
        } catch (JsonException $e) {
            throw new RuntimeException('Failed to compare user IDs', 0, $e);
        }

        return match ($entryGroupUserAuthUser->role->value) {
            RoleEnum::ADMIN => true,
            RoleEnum::MODERATOR => $entryGroupUserTargetUser->role->value === RoleEnum::MEMBER,
            default => false,
        };
    }

    public function canChangeUserRoleInEntryGroup(
        EntryGroupUser $entryGroupUserAuthUser,
    ): bool {
        return match ($entryGroupUserAuthUser->role->value) {
            RoleEnum::ADMIN => true,
            default => false,
        };
    }

    /**
     * @throws RandomException
     */
    public function generateEntryGroupAesPassword(): string
    {
        return random_bytes(32);
    }
}
