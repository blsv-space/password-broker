<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryGroupUser\Job;

use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\PasswordBroker\Application\EntryGroupUser\Event\EntryGroupUserRoleChangedEvent;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Entity\EntryGroupUser;
use App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject\Role;
use App\Module\PasswordBroker\Infrastructure\EntryGroupUser\Repository\EntryGroupUserRepository;
use App\Shared\Application\Job\AbstractReplicableSyncJob;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use InvalidArgumentException;
use JsonException;

final class ChangeUserRoleInGroupSyncJob extends AbstractReplicableSyncJob
{
    public const string PAYLOAD_KEY_USER_ID = EntryGroupUserRepository::FIELD_USER_ID;
    public const string PAYLOAD_KEY_ENTRY_GROUP_ID = EntryGroupUserRepository::FIELD_ENTRY_GROUP_ID;
    public const string PAYLOAD_KEY_ROLE = EntryGroupUserRepository::FIELD_ROLE;

    /**
     * @throws PersistenceException
     */
    #[\Override]
    public function handle(): EntryGroupUser
    {
        $this->validate();

        $entryGroupUserRepository = EntryGroupUserRepository::getInstance();
        $entryGroupUser = $entryGroupUserRepository->findByUserIdAndEntryGroupId(
            userId: UserId::fromRaw($this->payload[self::PAYLOAD_KEY_USER_ID]),
            entryGroupId: EntryGroupId::fromRaw($this->payload[self::PAYLOAD_KEY_ENTRY_GROUP_ID]),
        );

        if (is_null($entryGroupUser)) {
            throw new InvalidArgumentException('Entry Group User not found');
        }

        $role = Role::fromRaw($this->payload[self::PAYLOAD_KEY_ROLE]);
        try {
            if ($entryGroupUser->role->equals($role)) {
                return $entryGroupUser;
            }
        } catch (JsonException $_) {
            //ignore
        }

        $entryGroupUser->role = $role;

        $entryGroupUserRepository->save($entryGroupUser);

        EventDispatcher::getInstance()->dispatch(new EntryGroupUserRoleChangedEvent($entryGroupUser));

        return $entryGroupUser;
    }

    private function validate(): void
    {
        if (empty($this->payload[self::PAYLOAD_KEY_USER_ID])) {
            throw new InvalidArgumentException('User id is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_ENTRY_GROUP_ID])) {
            throw new InvalidArgumentException('Entry Group id is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_ROLE])) {
            throw new InvalidArgumentException('Role is required');
        }

        Role::validate($this->payload[self::PAYLOAD_KEY_ROLE]);
    }
}
