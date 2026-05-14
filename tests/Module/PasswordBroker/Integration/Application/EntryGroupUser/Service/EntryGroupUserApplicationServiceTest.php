<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryGroupUser\Service;

use App\Module\Identity\Application\User\Service\Exception\AuthException;
use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\EntryGroupUserApplicationService;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\AuthUserHasNoRights;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\AuthUserNotInEntryGroupException;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\EntryGroupUserNotFoundException;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\TargetGroupNotFoundException;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\TargetUserNotFoundException;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\TargetUserNotInEntryGroupException;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Enum\RoleEnum;
use App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject\Role;
use App\Module\PasswordBroker\Infrastructure\EntryGroupUser\Repository\EntryGroupUserRepository;
use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;
use App\Shared\Infrastructure\Security\Exception\JwtTokenExpiredException;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use Random\RandomException;
use ReflectionException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupUserFixture;
use Tests\Shared\IntegrationTestCase;

class EntryGroupUserApplicationServiceTest extends IntegrationTestCase
{
    private User $user;

    /**
     * @throws ReflectionException
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    #[\Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->user = UserFixture::create(persist: true);
        $this->actAs($this->user);
    }

    /**
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_get_entry_group_users_by(): void
    {
        $entryGroupUserApplicationService = EntryGroupUserApplicationService::getInstance();
        $entryGroupUser = EntryGroupUserFixture::create(persist: true);
        EntryGroupUserFixture::createMany(
            count: 5,
            attributes: [EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw()],
        );
        EntryGroupUserFixture::create(persist: true);

        $entryGroupUsers = $entryGroupUserApplicationService->getEntryGroupUsersBy([
            new QueryCriteria(
                field: EntryGroupUserRepository::FIELD_ENTRY_GROUP_ID,
                value: $entryGroupUser->entryGroupId->toRaw(),
            ),
        ]);
        $this->assertCount(6, $entryGroupUsers);
    }

    /**
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_count_entry_group_users_by(): void
    {
        $entryGroupUserApplicationService = EntryGroupUserApplicationService::getInstance();
        $entryGroupUser = EntryGroupUserFixture::create(persist: true);
        EntryGroupUserFixture::createMany(count: 7, attributes: [EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw()]);
        EntryGroupUserFixture::createMany(count: 2);

        $countEntryGroupUsersBy = $entryGroupUserApplicationService->countEntryGroupUsersBy([
            new QueryCriteria(
                field: EntryGroupUserRepository::FIELD_ENTRY_GROUP_ID,
                value: $entryGroupUser->entryGroupId->toRaw(),
            ),
        ]);

        $this->assertEquals(8, $countEntryGroupUsersBy);
    }

    /**
     * @throws RandomException
     * @throws TargetGroupNotFoundException
     * @throws TargetUserNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_add_first_user_to_group(): void
    {
        $entryGroupUserApplicationService = EntryGroupUserApplicationService::getInstance();
        $entryGroup = EntryGroupFixture::create(persist: true);
        $user = UserFixture::create(persist: true);

        $entryGroupUserApplicationService->addFirstUserToGroup(
            targetUserId: $user->getId(),
            entryGroupId: $entryGroup->getId(),
        );

        $this->assertDatabaseHas(
            table: EntryGroupUserFixture::getTableName(),
            param: [
                EntryGroupUserFixture::USER_ID => $user->getId()->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->getId()->toRaw(),
            ],
        );
    }

    /**
     * @throws TargetGroupNotFoundException
     * @throws AuthUserNotInEntryGroupException
     * @throws AuthUserHasNoRights
     * @throws JwtTokenExpiredException
     * @throws JwtInvalidTokenException
     * @throws AuthException
     * @throws TargetUserNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_add_user_to_group(): void
    {
        $entryGroupUserApplicationService = EntryGroupUserApplicationService::getInstance();
        $entryGroupUser = EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $this->user->getId()->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::ADMIN->value,
            ],
            persist: true,
        );
        $user = UserFixture::create(persist: true);

        $entryGroupUserApplicationService->addUserToGroup(
            targetUserId: $user->getId(),
            entryGroupId: $entryGroupUser->entryGroupId,
            role: Role::fromRaw(RoleEnum::MEMBER->value),
            authUserMasterPassword: UserFixture::DEFAULT_MASTER_PASSWORD,
        );

        $this->assertDatabaseHas(
            table: EntryGroupUserFixture::getTableName(),
            param: [
                EntryGroupUserFixture::USER_ID => $user->getId()->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
            ],
        );
    }

    /**
     * @throws TargetGroupNotFoundException
     * @throws AuthUserNotInEntryGroupException
     * @throws AuthUserHasNoRights
     * @throws JwtTokenExpiredException
     * @throws JwtInvalidTokenException
     * @throws AuthException
     * @throws TargetUserNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     * @throws TargetUserNotInEntryGroupException
     */
    public function test_it_should_delete_user_from_group(): void
    {
        $entryGroupUserApplicationService = EntryGroupUserApplicationService::getInstance();
        $entryGroupUserOwn = EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $this->user->getId()->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::ADMIN->value,

            ],
            persist: true,
        );
        $entryGroupUserTarget = EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUserOwn->entryGroupId->toRaw(),
            ],
            persist: true,
        );
        $entryGroupUserApplicationService->deleteUserFromGroup(
            targetUserId: $entryGroupUserTarget->userId,
            entryGroupId: $entryGroupUserOwn->entryGroupId,
        );

        $this->assertDatabaseMissing(
            table: EntryGroupUserFixture::getTableName(),
            param: [
                EntryGroupUserFixture::USER_ID => $entryGroupUserTarget->userId->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUserOwn->entryGroupId->toRaw(),
            ],
        );
    }

    /**
     * @throws EntryGroupUserNotFoundException
     * @throws AuthUserNotInEntryGroupException
     * @throws AuthUserHasNoRights
     * @throws JwtTokenExpiredException
     * @throws JwtInvalidTokenException
     * @throws AuthException
     * @throws TargetUserNotFoundException
     * @throws RsaDomainServiceException
     * @throws TargetUserNotInEntryGroupException
     * @throws PersistenceException
     */
    public function test_it_should_delete_entry_user_group_sync(): void
    {
        $entryGroupUserApplicationService = EntryGroupUserApplicationService::getInstance();
        $entryGroupUserOwn = EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $this->user->getId()->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::ADMIN->value,

            ],
            persist: true,
        );

        $entryGroupUserApplicationService->deleteEntryUserGroupSync(entryGroupUserId: $entryGroupUserOwn->id);

        $this->assertDatabaseMissing(
            table: EntryGroupUserFixture::getTableName(),
            param: [
                EntryGroupUserFixture::ID => $entryGroupUserOwn->id->toRaw(),
            ],
        );
    }

    /**
     * @throws TargetGroupNotFoundException
     * @throws AuthUserNotInEntryGroupException
     * @throws AuthUserHasNoRights
     * @throws JwtTokenExpiredException
     * @throws JwtInvalidTokenException
     * @throws AuthException
     * @throws TargetUserNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     * @throws TargetUserNotInEntryGroupException
     */
    public function test_it_should_change_user_role(): void
    {
        $entryGroupUserApplicationService = EntryGroupUserApplicationService::getInstance();
        $entryGroupUserOwn = EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $this->user->getId()->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::ADMIN->value,
            ],
            persist: true,
        );
        $entryGroupUserTarget = EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUserOwn->entryGroupId->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::MEMBER->value,
            ],
            persist: true,
        );

        $entryGroupUserApplicationService->changeUserRole(
            targetUserId: $entryGroupUserTarget->userId,
            entryGroupId: $entryGroupUserTarget->entryGroupId,
            role: Role::fromRaw(RoleEnum::MODERATOR->value),
        );

        $this->assertDatabaseHas(
            table: EntryGroupUserFixture::getTableName(),
            param: [
                EntryGroupUserFixture::USER_ID => $entryGroupUserTarget->userId->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUserTarget->entryGroupId->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::MODERATOR->value,
            ],
        );
    }

    /**
     * @throws EntryGroupUserNotFoundException
     * @throws TargetGroupNotFoundException
     * @throws AuthUserNotInEntryGroupException
     * @throws AuthUserHasNoRights
     * @throws JwtTokenExpiredException
     * @throws JwtInvalidTokenException
     * @throws AuthException
     * @throws TargetUserNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     * @throws TargetUserNotInEntryGroupException
     */
    public function test_it_should_change_user_role_by_entry_group_user_id(): void
    {
        $entryGroupUserApplicationService = EntryGroupUserApplicationService::getInstance();
        $entryGroupUserOwn = EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $this->user->getId()->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::ADMIN->value,
            ],
            persist: true,
        );
        $entryGroupUserTarget = EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUserOwn->entryGroupId->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::MEMBER->value,
            ],
            persist: true,
        );

        $entryGroupUserApplicationService->changeUserRoleByEntryGroupUserId(
            entryGroupUserId: $entryGroupUserTarget->id,
            role: Role::fromRaw(RoleEnum::MODERATOR->value),
        );

        $this->assertDatabaseHas(
            table: EntryGroupUserFixture::getTableName(),
            param: [
                EntryGroupUserFixture::ID => $entryGroupUserTarget->id->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::MODERATOR->value,
            ],
        );
    }

    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_get_entry_group_user_by_id(): void
    {
        $entryGroupUserApplicationService = EntryGroupUserApplicationService::getInstance();
        $entryGroupUser = EntryGroupUserFixture::create(persist: true);
        $result = $entryGroupUserApplicationService->getEntryGroupUserById($entryGroupUser->id);
        $this->assertEquals($entryGroupUser->id, $result->id);
    }

    /**
     * @throws AuthException
     * @throws AuthUserNotInEntryGroupException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_get_entry_group_user_for_auth_user_by_and_entry_group_id(): void
    {
        $entryGroupUserApplicationService = EntryGroupUserApplicationService::getInstance();
        $entryGroupUserOwn = EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $this->user->getId()->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::ADMIN->value,
            ],
            persist: true,
        );

        $entryGroupUser = $entryGroupUserApplicationService->getEntryGroupUseForAuthUserByAndEntryGroupId($entryGroupUserOwn->entryGroupId);

        $this->assertEquals($entryGroupUserOwn->id->toRaw(), $entryGroupUser->id->toRaw());
    }

}
