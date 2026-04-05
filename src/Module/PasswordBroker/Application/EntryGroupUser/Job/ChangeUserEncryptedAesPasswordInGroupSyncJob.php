<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryGroupUser\Job;

use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\PasswordBroker\Application\EntryGroupUser\Event\EntryGroupUserAesEncryptedPasswordChangedEvent;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Entity\EntryGroupUser;
use App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject\EncryptedAesPassword;
use App\Module\PasswordBroker\Infrastructure\EntryGroupUser\Repository\EntryGroupUserRepository;
use App\Shared\Application\Job\AbstractReplicableSyncJob;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use InvalidArgumentException;
use JsonException;

final class ChangeUserEncryptedAesPasswordInGroupSyncJob extends AbstractReplicableSyncJob
{
    public const string PAYLOAD_KEY_USER_ID = EntryGroupUserRepository::FIELD_USER_ID;
    public const string PAYLOAD_KEY_ENTRY_GROUP_ID = EntryGroupUserRepository::FIELD_ENTRY_GROUP_ID;
    public const string PAYLOAD_KEY_ENCRYPTED_AES_PASSWORD = EntryGroupUserRepository::FIELD_ENCRYPTED_AES_PASSWORD;

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

        $encryptedAesPassword = EncryptedAesPassword::fromRaw($this->payload[self::PAYLOAD_KEY_ENCRYPTED_AES_PASSWORD]);
        try {
            if ($entryGroupUser->encryptedAesPassword->equals($encryptedAesPassword)) {
                return $entryGroupUser;
            }
        } catch (JsonException $_) {
            //ignore
        }

        $entryGroupUser->encryptedAesPassword = $encryptedAesPassword;

        $entryGroupUserRepository->save($entryGroupUser);

        EventDispatcher::getInstance()->dispatch(new EntryGroupUserAesEncryptedPasswordChangedEvent($entryGroupUser));

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

        if (empty($this->payload[self::PAYLOAD_KEY_ENCRYPTED_AES_PASSWORD])) {
            throw new InvalidArgumentException('Encrypted Aes Password is required');
        }
    }
}
