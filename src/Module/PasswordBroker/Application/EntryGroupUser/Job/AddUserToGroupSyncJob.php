<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryGroupUser\Job;

use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\Identity\Infrastructure\User\Repository\UserRepository;
use App\Module\PasswordBroker\Application\EntryGroupUser\Event\EntryGroupUserCreatedEvent;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Entity\EntryGroupUser;
use App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject\Role;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\Repository\EntryGroupRepository;
use App\Module\PasswordBroker\Infrastructure\EntryGroupUser\Repository\EntryGroupUserRepository;
use App\Shared\Application\Job\AbstractReplicableSyncJob;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use InvalidArgumentException;
use Override;

class AddUserToGroupSyncJob extends AbstractReplicableSyncJob
{
    public const string PAYLOAD_KEY_ID = EntryGroupUserRepository::FIELD_ID;
    public const string PAYLOAD_KEY_USER_ID = EntryGroupUserRepository::FIELD_USER_ID;
    public const string PAYLOAD_KEY_ENTRY_GROUP_ID = EntryGroupUserRepository::FIELD_ENTRY_GROUP_ID;
    public const string PAYLOAD_KEY_ROLE = EntryGroupUserRepository::FIELD_ROLE;
    public const string PAYLOAD_KEY_ENCRYPTED_AES_PASSWORD = EntryGroupUserRepository::FIELD_ENCRYPTED_AES_PASSWORD;


    /**
     * @throws PersistenceException
     */
    #[Override]
    public function handle(): EntryGroupUser
    {
        $this->validate();

        $entryGroupUserRepository = EntryGroupUserRepository::getInstance();
        $user = UserRepository::getInstance()->findById(UserId::fromRaw($this->payload[self::PAYLOAD_KEY_USER_ID]));
        if (is_null($user)) {
            throw new InvalidArgumentException('User not found');
        }
        $entryGroup = EntryGroupRepository::getInstance()
            ->findById(EntryGroupId::fromRaw($this->payload[self::PAYLOAD_KEY_ENTRY_GROUP_ID]));
        if (is_null($entryGroup)) {
            throw new InvalidArgumentException('Entry Group not found');
        }
        if ($entryGroupUserRepository->isUserInEntryGroup($user->getId(), $entryGroup->getId())) {
            throw new InvalidArgumentException('User is already in the entry group');
        }
        $entryGroupUser = $entryGroupUserRepository->mapArrayToEntity($this->payload);

        $entryGroupUserRepository->save($entryGroupUser);
        EventDispatcher::getInstance()->dispatch(new EntryGroupUserCreatedEvent($entryGroupUser));

        return $entryGroupUser;
    }


    private function validate(): void
    {
        if (empty($this->payload[self::PAYLOAD_KEY_ID])) {
            throw new InvalidArgumentException('Entry Group id is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_USER_ID])) {
            throw new InvalidArgumentException('User id is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_ENTRY_GROUP_ID])) {
            throw new InvalidArgumentException('Entry Group id is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_ROLE])) {
            throw new InvalidArgumentException('Role is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_ENCRYPTED_AES_PASSWORD])) {
            throw new InvalidArgumentException('Encrypted AES Password is required');
        }

        Role::validate($this->payload[self::PAYLOAD_KEY_ROLE]);
    }
}
