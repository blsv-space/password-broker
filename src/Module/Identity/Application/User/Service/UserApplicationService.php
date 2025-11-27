<?php

namespace App\Module\Identity\Application\User\Service;

use App\Module\Identity\Application\User\Job\CreateUserSyncJob;
use App\Module\Identity\Application\User\Job\DeleteUserSyncJob;
use App\Module\Identity\Application\User\Job\UpdateUserSyncJob;
use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\UserDomainService;
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
     * @throws Throwable
     */
    public function createUserSync(
        string $userName,
        string $password,
    ): User
    {
        return new CreateUserSyncJob([
            'userName' => $userName,
            'password' => $password,
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