<?php

namespace App\Module\Identity\Domain\User\Service;

use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Repository\UserRepositoryInterface;
use App\Module\Identity\Domain\User\ValueObject\HashedPassword;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\Identity\Domain\User\ValueObject\UserName;
use App\Module\Identity\Infrastructure\User\Repository\UserRepository;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\Id;
use App\Shared\Domain\ValueObject\UpdatedAt;
use Inquisition\Core\Domain\Service\DomainServiceInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use Inquisition\Foundation\Singleton\SingletonTrait;
use Throwable;

final class UserDomainService
    implements DomainServiceInterface
{
    private UserRepositoryInterface $userRepository;

    use SingletonTrait;

    private function __construct()
    {
        $this->userRepository = UserRepository::getInstance();
    }

    /**
     * @param Id $id
     * @return User|null
     * @throws PersistenceException
     */
    public function findUserById(Id $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    /**
     * @param QueryCriteria[] $criteria
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return User[]
     * @throws PersistenceException
     */
    public function findBy(
        array  $criteria = [],
        ?array $orderBy = null,
        ?int   $limit = null,
        ?int   $offset = null,
    ): array
    {
        return $this->userRepository->findBy(
            criteria: $criteria,
            orderBy: $orderBy,
            limit: $limit,
            offset: $offset,
        );
    }

    /**
     * @param QueryCriteria[] $criteria
     * @return int
     * @throws PersistenceException
     */
    public function count(array $criteria = []): int
    {
        return $this->userRepository->count($criteria);
    }

    /**
     * @param UserName $userName
     * @return User|null
     * @throws PersistenceException
     */
    public function findUserByUsername(UserName $userName): ?User
    {
        return $this->userRepository->findByUsername($userName);
    }

    /**
     * @param array $array
     * @return User
     */
    public function mapArrayToEntity(array $array): User
    {
        $createdAt = isset($array['createdAt']) ? CreatedAt::fromRaw($array['createdAt']) : null;
        $updateAt = isset($array['updatedAt']) ? UpdatedAt::fromRaw($array['updatedAt']) : null;

        return new User(
            userName: UserName::fromRaw($array['userName']),
            hashedPassword: HashedPassword::fromRaw($array['hashedPassword']),
            id: !empty($array['id']) ? UserId::fromRaw($array['id']) : null,
            createdAt: $createdAt,
            updatedAt: $updateAt,
        );
    }

    /**
     * @param User $user
     * @return void
     * @throws Throwable
     */
    public function save(User $user): void
    {
        $this->userRepository->save($user);
    }

    /**
     * @param User $user
     * @return void
     */
    public function delete(User $user): void
    {
        $this->userRepository->removeById($user);
    }

}