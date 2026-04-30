<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Fixture;

use App\Module\Identity\Application\User\Service\UserApplicationService;
use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\Identity\Domain\User\Service\RsaDomainService;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Entity\EntryGroupUser;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Enum\RoleEnum;
use App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject\EncryptedAesPassword;
use App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject\EntryGroupUserId;
use App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject\Role;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\Repository\EntryGroupRepository;
use App\Module\PasswordBroker\Infrastructure\EntryGroupUser\Repository\EntryGroupUserRepository;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\DateTime;
use App\Shared\Domain\ValueObject\UpdatedAt;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Shared\AbstractFixture;

class EntryGroupUserFixture extends AbstractFixture
{
    public const string ID = EntryGroupUserRepository::FIELD_ID;
    public const string ENTRY_GROUP_ID = EntryGroupUserRepository::FIELD_ENTRY_GROUP_ID;
    public const string USER_ID = EntryGroupUserRepository::FIELD_USER_ID;
    public const string ROLE = EntryGroupUserRepository::FIELD_ROLE;
    public const string ENCRYPTED_AES_PASSWORD = EntryGroupUserRepository::FIELD_ENCRYPTED_AES_PASSWORD;
    public const string CREATED_AT = EntryGroupRepository::FIELD_CREATED_AT;
    public const string UPDATED_AT = EntryGroupRepository::FIELD_UPDATED_AT;

    public const string DEFAULT_ROLE = RoleEnum::MEMBER->value;
    public const string DEFAULT_AES_PASSWORD = 'asd_';

    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    #[\Override]
    public static function create(array $attributes = [], bool $persist = false): EntryGroupUser
    {
        $user = null;
        if (!array_key_exists(self::USER_ID, $attributes)) {
            $user = UserFixture::create(persist: true);
            $attributes[self::USER_ID] = $user->getId()->value;
        }
        if (!array_key_exists(self::ENTRY_GROUP_ID, $attributes)) {
            $entryGroup = EntryGroupFixture::create(persist: true);
            $attributes[self::ENTRY_GROUP_ID] = $entryGroup->getId()->value;
        }
        if (!array_key_exists(self::ENCRYPTED_AES_PASSWORD, $attributes)) {
            if (!$user) {
                $user = UserApplicationService::getInstance()->getUserByUuid($attributes[self::USER_ID]);
            }
            if (!$user) {
                throw new PersistenceException('User not found');
            }
            $rsaDomainService = RsaDomainService::getInstance();
            $publicKey = $rsaDomainService->getUserPublicKey($user);
            $attributes[self::ENCRYPTED_AES_PASSWORD] = $rsaDomainService->encryptByPublic(self::DEFAULT_AES_PASSWORD, $publicKey);
        }

        $entryGroupUser = new EntryGroupUser(
            id: EntryGroupUserId::fromRaw(static::generateId($attributes[self::ID] ?? EntryGroupId::generate()->toRaw())),
            entryGroupId: EntryGroupId::fromRaw($attributes[self::ENTRY_GROUP_ID]),
            userId: UserId::fromRaw($attributes[self::USER_ID]),
            role: Role::fromRaw($attributes[self::ROLE] ?? self::DEFAULT_ROLE),
            encryptedAesPassword: EncryptedAesPassword::fromRaw($attributes[self::ENCRYPTED_AES_PASSWORD] ?? ''),
            createdAt: CreatedAt::fromRaw($attributes[self::CREATED_AT]
                ?? static::faker()->dateTime()->format(DateTime::FORMAT)),
            updatedAt: UpdatedAt::fromRaw($attributes[self::UPDATED_AT]
                ?? static::faker()->dateTime()->format(DateTime::FORMAT)),
        );

        if ($persist) {
            static::persist($entryGroupUser);
        }

        return $entryGroupUser;
    }

    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    #[\Override]
    public static function createMany(int $count, array $attributes = [], bool $persist = true): array
    {
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = static::create($attributes, $persist);
        }

        return $out;
    }

    #[\Override]
    public static function getTableName(): string
    {
        return EntryGroupUserRepository::getTableName();
    }

}
