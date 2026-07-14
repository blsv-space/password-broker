<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryGroupUser\Repository;

use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Entity\EntryGroupUser;
use App\Shared\Infrastructure\Repository\EntityRepositoryInterface;
use Inquisition\Core\Domain\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<EntryGroupUser>
 * @extends EntityRepositoryInterface<EntryGroupUser>
 */
interface EntryGroupUserRepositoryInterface extends RepositoryInterface, EntityRepositoryInterface
{
    public function findByUserId(UserId $userId): array;
    public function findByEntryGroupId(EntryGroupId $entryGroupId): array;
    public function findByUserIdAndEntryGroupId(UserId $userId, EntryGroupId $entryGroupId): ?EntryGroupUser;
    public function isUserInEntryGroup(UserId $userId, EntryGroupId $entryGroupId): bool;
}
