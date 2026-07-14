<?php

declare(strict_types=1);

namespace App\Module\Identity\Application\User\Service;

use App\Module\Identity\Application\User\Job\CreateUserSyncJob;
use App\Module\Identity\Application\User\Job\DeleteUserSyncJob;
use App\Module\Identity\Application\User\Job\UpdateUserSyncJob;
use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\RsaDomainService;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\Identity\Infrastructure\Security\PasswordHasher;
use App\Module\Identity\Infrastructure\User\Repository\UserRepository;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\Id;
use App\Shared\Domain\ValueObject\UpdatedAt;
use Inquisition\Core\Application\Service\ApplicationServiceInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use Inquisition\Foundation\Singleton\SingletonTrait;
use Throwable;

final class UserApplicationService implements ApplicationServiceInterface
{
    use SingletonTrait;
    private UserRepository $userRepository;
    private PasswordHasher $passwordHasher;

    private function __construct()
    {
        $this->userRepository = UserRepository::getInstance();
        $this->passwordHasher = PasswordHasher::getInstance();
    }

    /**
     * @throws Throwable
     */
    public function createUserSync(
        string $userName,
        string $password,
        string $email,
        string $masterPassword,
        bool $isAdmin,
    ): User {
        $rsaKeyPair = RsaDomainService::getInstance()->generateKeyPair($masterPassword);
        $dateTime = CreatedAt::now();

        return new CreateUserSyncJob([
            CreateUserSyncJob::PAYLOAD_KEY_ID => UserId::generate()->toRaw(),
            CreateUserSyncJob::PAYLOAD_KEY_USER_NAME => $userName,
            CreateUserSyncJob::PAYLOAD_KEY_HASHED_PASSWORD => $this->passwordHasher->hash($password),
            CreateUserSyncJob::PAYLOAD_KEY_EMAIL => $email,
            CreateUserSyncJob::PAYLOAD_KEY_IS_ADMIN => $isAdmin,
            CreateUserSyncJob::PAYLOAD_KEY_RSA_PRIVATE_KEY => $rsaKeyPair->privateKey,
            CreateUserSyncJob::PAYLOAD_KEY_RSA_PUBLIC_KEY => $rsaKeyPair->publicKey,
            CreateUserSyncJob::PAYLOAD_CREATED_AT => $dateTime->toRaw(),
            CreateUserSyncJob::PAYLOAD_UPDATED_AT => $dateTime->toRaw(),
        ])->execute();
    }

    /**
     *
     * @throws Throwable
     */
    public function updateUserSync(
        string  $uuid,
        string  $userName,
        ?string $password = null,
    ): User {
        $dateTime = UpdatedAt::now();

        return new UpdateUserSyncJob([
            UpdateUserSyncJob::PAYLOAD_KEY_ID => $uuid,
            UpdateUserSyncJob::PAYLOAD_KEY_USER_NAME => $userName,
            UpdateUserSyncJob::PAYLOAD_KEY_HASHED_PASSWORD => $password ? $this->passwordHasher->hash($password) : null,
            UpdateUserSyncJob::PAYLOAD_UPDATED_AT => $dateTime->toRaw(),
        ])->execute();
    }

    /**
     * @throws Throwable
     */
    public function deleteUserSync(string $uuid): void
    {
        new DeleteUserSyncJob([DeleteUserSyncJob::PAYLOAD_KEY_ID => $uuid])->execute();
    }

    /**
     * @throws PersistenceException
     */
    public function getUserByUuid(string $uuid): ?User
    {
        return $this->userRepository->findById(Id::fromRaw($uuid));
    }

    /**
     * @throws PersistenceException
     */
    public function getUsersBy(
        array  $criteria = [],
        ?array $orderBy = null,
        ?int   $limit = null,
        ?int   $offset = null,
    ): array {
        return $this->userRepository->findBy(
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
    public function countUsersBy(array $criteria = []): int
    {
        return $this->userRepository->count($criteria);
    }

    /**
     * @throws PersistenceException
     */
    public function delete(User $user): void
    {
        $this->userRepository->softDelete($user);
    }

    /**
     * @throws PersistenceException
     */
    public function save(User $user): void
    {
        $this->userRepository->save($user);
    }

    /**
     * @throws PersistenceException
     */
    public function update(User $user): void
    {
        $this->userRepository->updateById($user);
    }
}
