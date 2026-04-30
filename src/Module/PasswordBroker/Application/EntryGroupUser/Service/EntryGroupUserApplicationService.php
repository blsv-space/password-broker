<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryGroupUser\Service;

use App\Module\Identity\Application\User\Service\AuthApplicationService;
use App\Module\Identity\Application\User\Service\Exception\AuthException;
use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Repository\UserRepositoryInterface;
use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\Identity\Domain\User\Service\RsaDomainService;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\Identity\Infrastructure\User\Repository\UserRepository;
use App\Module\PasswordBroker\Application\EntryGroupUser\Job\AddUserToGroupSyncJob;
use App\Module\PasswordBroker\Application\EntryGroupUser\Job\ChangeUserRoleInGroupSyncJob;
use App\Module\PasswordBroker\Application\EntryGroupUser\Job\DeleteUserFromGroupSyncJob;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\AuthUserHasNoRights;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\AuthUserNotInEntryGroupException;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\EntryGroupUserNotFoundException;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\TargetGroupNotFoundException;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\TargetUserNotFoundException;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\TargetUserNotInEntryGroupException;
use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use App\Module\PasswordBroker\Domain\EntryGroup\Repository\EntryGroupRepositoryInterface;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Entity\EntryGroupUser;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Enum\RoleEnum;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Repository\EntryGroupUserRepositoryInterface;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Service\EntryGroupUserDomainService;
use App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject\EntryGroupUserId;
use App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject\Role;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\Repository\EntryGroupRepository;
use App\Module\PasswordBroker\Infrastructure\EntryGroupUser\Repository\EntryGroupUserRepository;
use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;
use App\Shared\Infrastructure\Security\Exception\JwtTokenExpiredException;
use Inquisition\Core\Application\Service\ApplicationServiceInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use Inquisition\Foundation\Singleton\SingletonTrait;
use Random\RandomException;

class EntryGroupUserApplicationService implements ApplicationServiceInterface
{
    use SingletonTrait;

    private UserRepositoryInterface $userRepository;
    private EntryGroupRepositoryInterface $entryGroupRepository;
    private EntryGroupUserRepositoryInterface $entryGroupUserRepository;
    private EntryGroupUserDomainService $entryGroupUserDomainService;
    private RsaDomainService $rsaDomainService;

    public function __construct()
    {
        $this->userRepository = UserRepository::getInstance();
        $this->entryGroupRepository = EntryGroupRepository::getInstance();
        $this->entryGroupUserRepository = EntryGroupUserRepository::getInstance();
        $this->entryGroupUserDomainService = EntryGroupUserDomainService::getInstance();
        $this->rsaDomainService = RsaDomainService::getInstance();
    }

    /**
     * @param  QueryCriteria[]      $criteria
     * @throws PersistenceException
     */
    public function getEntryGroupUsersBy(
        array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array {
        return $this->entryGroupUserRepository->findBy(
            criteria: $criteria,
            orderBy: $orderBy,
            limit: $limit,
            offset: $offset,
        );
    }

    /**
     * @param  QueryCriteria[]      $criteria
     * @throws PersistenceException
     */
    public function countEntryGroupUsersBy(array $criteria = []): int
    {
        return $this->entryGroupUserRepository->count($criteria);
    }

    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     * @throws TargetGroupNotFoundException
     * @throws TargetUserNotFoundException
     * @throws RandomException
     */
    public function addFirstUserToGroup(UserId $targetUserId, EntryGroupId $entryGroupId): EntryGroupUser
    {
        $targetUser = $this->getTargetUser($targetUserId);
        $this->getTargetEntryGroup($entryGroupId);
        $targetUserPublicKey = $this->rsaDomainService->getUserPublicKey(user: $targetUser);
        $entryGroupAesPassword = $this->entryGroupUserDomainService->generateEntryGroupAesPassword();
        $entryGroupAesPasswordEncrypted = $this->rsaDomainService->encryptByPublic(
            data: $entryGroupAesPassword,
            publicKey: $targetUserPublicKey,
        );

        return new AddUserToGroupSyncJob([
            AddUserToGroupSyncJob::PAYLOAD_KEY_ID => EntryGroupUserId::generate()->toRaw(),
            AddUserToGroupSyncJob::PAYLOAD_KEY_USER_ID => $targetUser->getId()->toRaw(),
            AddUserToGroupSyncJob::PAYLOAD_KEY_ENTRY_GROUP_ID => $entryGroupId->toRaw(),
            AddUserToGroupSyncJob::PAYLOAD_KEY_ROLE => RoleEnum::ADMIN->value,
            AddUserToGroupSyncJob::PAYLOAD_KEY_ENCRYPTED_AES_PASSWORD => $entryGroupAesPasswordEncrypted,
        ])->handle();
    }

    /**
     * @throws AuthException
     * @throws AuthUserHasNoRights
     * @throws AuthUserNotInEntryGroupException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws PersistenceException
     * @throws TargetGroupNotFoundException
     * @throws TargetUserNotFoundException
     * @throws RsaDomainServiceException
     */
    public function addUserToGroup(UserId $targetUserId, EntryGroupId $entryGroupId, Role $role, string $authUserMasterPassword): EntryGroupUser
    {
        $targetUser = $this->getTargetUser($targetUserId);
        $this->getTargetEntryGroup($entryGroupId);
        $authUser = $this->getAuthUser();
        $entryGroupUserAuth = $this->getEntryGroupUserAuth($authUser, $entryGroupId);

        if (!$this->entryGroupUserDomainService->canAddUserWithRoleToEntryGroup(
            role: $role,
            entryGroupUserAuthUser: $entryGroupUserAuth,
        )
        ) {
            throw new AuthUserHasNoRights();
        }

        $authUserPrivateKey = $this->rsaDomainService->getUserPrivateKey(
            userId: $authUser->id,
            masterPassword: $authUserMasterPassword,
        );

        $entryGroupAesPassword = $this->rsaDomainService->decryptByPrivate(
            data: $entryGroupUserAuth->encryptedAesPassword->toRaw(),
            privateKey: $authUserPrivateKey,
        );

        $targetUserPublicKey = $this->rsaDomainService->getUserPublicKey(user: $targetUser);

        $entryGroupAesPasswordEncrypted = $this->rsaDomainService->encryptByPublic(
            data: $entryGroupAesPassword,
            publicKey: $targetUserPublicKey,
        );

        return new AddUserToGroupSyncJob([
            AddUserToGroupSyncJob::PAYLOAD_KEY_ID => EntryGroupUserId::generate()->toRaw(),
            AddUserToGroupSyncJob::PAYLOAD_KEY_USER_ID => $targetUser->getId()->toRaw(),
            AddUserToGroupSyncJob::PAYLOAD_KEY_ENTRY_GROUP_ID => $entryGroupId->toRaw(),
            AddUserToGroupSyncJob::PAYLOAD_KEY_ROLE => $role->value,
            AddUserToGroupSyncJob::PAYLOAD_KEY_ENCRYPTED_AES_PASSWORD => $entryGroupAesPasswordEncrypted,
        ])->handle();
    }

    /**
     * @throws AuthException
     * @throws AuthUserHasNoRights
     * @throws AuthUserNotInEntryGroupException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws PersistenceException
     * @throws TargetGroupNotFoundException
     * @throws TargetUserNotFoundException
     * @throws TargetUserNotInEntryGroupException
     */
    public function deleteUserFromGroup(UserId $targetUserId, EntryGroupId $entryGroupId): void
    {
        $targetUser = $this->getTargetUser($targetUserId);

        $this->getTargetEntryGroup($entryGroupId);

        $authUser = $this->getAuthUser();

        $entryGroupUserAuth = $this->getEntryGroupUserAuth($authUser, $entryGroupId);

        $entryGroupUserTarget = $this->getEntryGroupUserTarget($targetUser, $entryGroupId, $targetUserId);

        if (!$this->entryGroupUserDomainService->canDeleteUserFromEntryGroup(
            entryGroupUserTargetUser: $entryGroupUserTarget,
            entryGroupUserAuthUser: $entryGroupUserAuth,
        )) {
            throw new AuthUserHasNoRights();
        }

        new DeleteUserFromGroupSyncJob([
            DeleteUserFromGroupSyncJob::PAYLOAD_KEY_USER_ID => $targetUser->getId()->toRaw(),
            DeleteUserFromGroupSyncJob::PAYLOAD_KEY_ENTRY_GROUP_ID => $entryGroupId->toRaw(),
        ])->handle();
    }

    /**
     * @throws AuthException
     * @throws AuthUserHasNoRights
     * @throws AuthUserNotInEntryGroupException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws PersistenceException
     * @throws TargetUserNotFoundException
     * @throws TargetUserNotInEntryGroupException
     */
    public function deleteEntryUserGroupSync(EntryGroupUserId $entryGroupUserId): void
    {
        $entryGroupUser = $this->getEntryGroupUserById($entryGroupUserId);
        $authUser = $this->getAuthUser();
        $entryGroupId = $entryGroupUser->entryGroupId;
        $targetUserId = $entryGroupUser->userId;
        $targetUser = $this->getTargetUser($targetUserId);

        $entryGroupUserAuth = $this->getEntryGroupUserAuth($authUser, $entryGroupId);

        $entryGroupUserTarget = $this->getEntryGroupUserTarget($targetUser, $entryGroupId, $targetUserId);

        if (!$this->entryGroupUserDomainService->canDeleteUserFromEntryGroup(
            entryGroupUserTargetUser: $entryGroupUserTarget,
            entryGroupUserAuthUser: $entryGroupUserAuth,
        )) {
            throw new AuthUserHasNoRights();
        }

        new DeleteUserFromGroupSyncJob([
            DeleteUserFromGroupSyncJob::PAYLOAD_KEY_USER_ID => $targetUserId->toRaw(),
            DeleteUserFromGroupSyncJob::PAYLOAD_KEY_ENTRY_GROUP_ID => $entryGroupId->toRaw(),
        ])->handle();
    }

    /**
     * @throws AuthException
     * @throws AuthUserHasNoRights
     * @throws AuthUserNotInEntryGroupException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws PersistenceException
     * @throws TargetGroupNotFoundException
     * @throws TargetUserNotFoundException
     * @throws TargetUserNotInEntryGroupException
     */
    public function changeUserRole(UserId $targetUserId, EntryGroupId $entryGroupId, Role $role): EntryGroupUser
    {
        $targetUser = $this->getTargetUser($targetUserId);
        $entryGroup = $this->getTargetEntryGroup($entryGroupId);
        $authUser = $this->getAuthUser();
        $entryGroupUserAuth = $this->getEntryGroupUserAuth($authUser, $entryGroupId);
        $this->getEntryGroupUserTarget($targetUser, $entryGroupId, $targetUserId);

        if (!$this->entryGroupUserDomainService->canChangeUserRoleInEntryGroup(
            entryGroupUserAuthUser: $entryGroupUserAuth,
        )) {
            throw new AuthUserHasNoRights();
        }

        return new ChangeUserRoleInGroupSyncJob([
            ChangeUserRoleInGroupSyncJob::PAYLOAD_KEY_USER_ID => $targetUser->getId()->toRaw(),
            ChangeUserRoleInGroupSyncJob::PAYLOAD_KEY_ENTRY_GROUP_ID => $entryGroup->getId()->toRaw(),
            ChangeUserRoleInGroupSyncJob::PAYLOAD_KEY_ROLE => $role->value,
        ])->handle();
    }

    /**
     * @throws AuthException
     * @throws AuthUserHasNoRights
     * @throws AuthUserNotInEntryGroupException
     * @throws EntryGroupUserNotFoundException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws PersistenceException
     * @throws TargetGroupNotFoundException
     * @throws TargetUserNotFoundException
     * @throws TargetUserNotInEntryGroupException
     */
    public function changeUserRoleByEntryGroupUserId(EntryGroupUserId $entryGroupUserId, Role $role): EntryGroupUser
    {
        $entryGroupUser = $this->entryGroupUserRepository->findById($entryGroupUserId);

        if (!$entryGroupUser) {
            throw new EntryGroupUserNotFoundException($entryGroupUserId);
        }

        return $this->changeUserRole(
            targetUserId: $entryGroupUser->userId,
            entryGroupId: $entryGroupUser->entryGroupId,
            role: $role,
        );
    }

    /**
     * @throws PersistenceException
     */
    public function getEntryGroupUserById(EntryGroupUserId $entryGroupUserId): ?EntryGroupUser
    {
        return $this->entryGroupUserRepository->findById($entryGroupUserId);
    }

    /**
     * @throws PersistenceException
     * @throws TargetUserNotFoundException
     */
    private function getTargetUser(UserId $targetUserId): User
    {
        $targetUser = $this->userRepository->findById($targetUserId);

        if (!$targetUser) {
            throw new TargetUserNotFoundException($targetUserId);
        }

        return $targetUser;
    }

    /**
     * @throws PersistenceException
     * @throws TargetGroupNotFoundException
     */
    private function getTargetEntryGroup(EntryGroupId $entryGroupId): EntryGroup
    {
        $entryGroup = $this->entryGroupRepository->findById($entryGroupId);

        if (!$entryGroup) {
            throw new TargetGroupNotFoundException($entryGroupId);
        }

        return $entryGroup;
    }

    /**
     * @throws AuthException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws PersistenceException
     */
    private function getAuthUser(): User
    {
        $authUser = AuthApplicationService::getInstance()->authUser();
        if (!$authUser) {
            throw new AuthException('User not authenticated');
        }

        return $authUser;
    }

    /**
     * @throws AuthUserNotInEntryGroupException
     * @throws PersistenceException
     */
    private function getEntryGroupUserAuth(User $authUser, EntryGroupId $entryGroupId): EntryGroupUser
    {
        $entryGroupUserAuth = $this->entryGroupUserRepository->findByUserIdAndEntryGroupId($authUser->id, $entryGroupId);

        if (!$entryGroupUserAuth) {
            throw new AuthUserNotInEntryGroupException();
        }

        return $entryGroupUserAuth;
    }

    /**
     * @throws PersistenceException
     * @throws TargetUserNotInEntryGroupException
     */
    private function getEntryGroupUserTarget(User $targetUser, EntryGroupId $entryGroupId, UserId $targetUserId): EntryGroupUser
    {
        $entryGroupUserTarget = $this->entryGroupUserRepository->findByUserIdAndEntryGroupId($targetUser->id, $entryGroupId);

        if (!$entryGroupUserTarget) {
            throw new TargetUserNotInEntryGroupException($targetUserId);
        }

        return $entryGroupUserTarget;
    }
}
