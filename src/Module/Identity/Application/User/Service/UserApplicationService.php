<?php

namespace App\Module\Identity\Application\User\Service;

use App\Module\Identity\Application\User\Job\CreateUserSyncJob;
use App\Module\Identity\Application\User\Job\DeleteUserSyncJob;
use App\Module\Identity\Application\User\Job\UpdateUserSyncJob;
use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\RsaDomainService;
use App\Module\Identity\Domain\User\Service\UserDomainService;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Shared\Domain\ValueObject\Id;
use Inquisition\Core\Application\Service\ApplicationServiceInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Foundation\Singleton\SingletonTrait;
use Throwable;

final class UserApplicationService
    implements ApplicationServiceInterface
{
    private UserDomainService $userDomainService;

    use SingletonTrait;

    private function __construct()
    {
        $this->userDomainService = UserDomainService::getInstance();
    }

    /**
     * @param string $userName
     * @param string $password
     * @param string $email
     * @param string $masterPassword
     * @param bool $isAdmin
     * @return User
     * @throws Throwable
     */
    public function createUserSync(
        string $userName,
        string $password,
        string $email,
        string $masterPassword,
        bool $isAdmin,
    ): User
    {
        $rsaKeyPair = RsaDomainService::getInstance()->generateKeyPair($masterPassword);

        return new CreateUserSyncJob([
            CreateUserSyncJob::PAYLOAD_KEY_ID => UserId::generate()->toRaw(),
            CreateUserSyncJob::PAYLOAD_KEY_USER_NAME => $userName,
            CreateUserSyncJob::PAYLOAD_KEY_PASSWORD => $password,
            CreateUserSyncJob::PAYLOAD_KEY_EMAIL => $email,
            CreateUserSyncJob::PAYLOAD_KEY_IS_ADMIN => $isAdmin,
            CreateUserSyncJob::PAYLOAD_KEY_RSA_PRIVATE_KEY => $rsaKeyPair->privateKey,
            CreateUserSyncJob::PAYLOAD_KEY_RSA_PUBLIC_KEY => $rsaKeyPair->publicKey,
        ])->execute();
    }

    /**
     * @param string $uuid
     * @param string $userName
     * @param string|null $password
     *
     * @return User
     * @throws Throwable
     */
    public function updateUser(
        string  $uuid,
        string  $userName,
        ?string $password = null,
    ): User
    {
        return new UpdateUserSyncJob([
            'id' => $uuid,
            'userName' => $userName,
            'password' => $password,
        ])->execute();
    }

    /**
     * @param string $uuid
     * @return void
     * @throws Throwable
     */
    public function deleteUser(string $uuid): void
    {
        new DeleteUserSyncJob(['id' => $uuid])->execute();
    }

    /**
     * @param string $uuid
     * @return User|null
     * @throws PersistenceException
     */
    public function getUserByUud(string $uuid): ?User
    {
        return $this->userDomainService->findUserById(Id::fromRaw($uuid));
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     * @throws PersistenceException
     */
    public function getUsersBy(
        array  $criteria = [],
        ?array $orderBy = null,
        ?int   $limit = null,
        ?int   $offset = null,
    ): array
    {
        return $this->userDomainService->findBy(
            criteria: $criteria,
            orderBy: $orderBy,
            limit: $limit,
            offset: $offset,
        );
    }

    /**
     * @param array $criteria
     * @return int
     * @throws PersistenceException
     */
    public function countUsersBy(array $criteria = []): int
    {
        return $this->userDomainService->count($criteria);
    }
}