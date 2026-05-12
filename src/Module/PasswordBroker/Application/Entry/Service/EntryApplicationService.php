<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\Entry\Service;

use App\Module\Identity\Application\User\Service\AuthApplicationService;
use App\Module\Identity\Application\User\Service\Exception\AuthException;
use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\Identity\Domain\User\Service\RsaDomainService;
use App\Module\PasswordBroker\Application\Entry\Job\CreateEntrySyncJob;
use App\Module\PasswordBroker\Application\Entry\Job\DeleteEntrySyncJob;
use App\Module\PasswordBroker\Application\Entry\Job\MoveEntrySyncJob;
use App\Module\PasswordBroker\Application\Entry\Job\RenameEntrySyncJob;
use App\Module\PasswordBroker\Domain\Entry\Entity\Entry;
use App\Module\PasswordBroker\Domain\Entry\ValueObject\EntryId;
use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Infrastructure\Entry\Repository\EntryRepository;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\Repository\EntryGroupRepository;
use App\Module\PasswordBroker\Infrastructure\EntryGroupUser\Repository\EntryGroupUserRepository;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\UpdatedAt;
use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;
use App\Shared\Infrastructure\Security\Exception\JwtTokenExpiredException;
use Inquisition\Core\Application\Service\ApplicationServiceInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryOperatorEnum;
use Inquisition\Foundation\Singleton\SingletonTrait;
use InvalidArgumentException;
use Throwable;

class EntryApplicationService implements ApplicationServiceInterface
{
    use SingletonTrait;
    private EntryRepository $entryRepository;
    private EntryGroupRepository $entryGroupRepository;
    private EntryGroupUserRepository $entryGroupUserRepository;
    private RsaDomainService $rsaDomainService;

    private function __construct()
    {
        $this->entryRepository = EntryRepository::getInstance();
        $this->entryGroupRepository = EntryGroupRepository::getInstance();
        $this->entryGroupUserRepository = EntryGroupUserRepository::getInstance();
        $this->rsaDomainService = RsaDomainService::getInstance();
    }

    /**
     * @throws Throwable
     */
    public function createEntrySync(
        string     $title,
        EntryGroup $entryGroup,
    ): Entry {

        return new CreateEntrySyncJob([
            CreateEntrySyncJob::PAYLOAD_KEY_ID => EntryGroupId::generate()->toRaw(),
            CreateEntrySyncJob::PAYLOAD_KEY_TITLE => $title,
            CreateEntrySyncJob::PAYLOAD_KEY_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            CreateEntrySyncJob::PAYLOAD_CREATED_AT => CreatedAt::now()->toRaw(),
        ])->execute();
    }

    /**
     * @throws PersistenceException
     * @throws Throwable
     */
    public function createEntryFromPrimitivesSync(
        string $title,
        string $entryGroupId,
    ): Entry {
        $entryGroup = $this->entryGroupRepository->findById(EntryId::fromRaw($entryGroupId));

        if (!$entryGroup) {
            throw new InvalidArgumentException("Entry Group with id $entryGroupId not found");
        }

        return $this->createEntrySync(
            title: $title,
            entryGroup: $entryGroup,
        );
    }

    /**
     * @throws PersistenceException
     */
    public function renameEntrySync(string $uuid, string $title): Entry
    {
        return new RenameEntrySyncJob([
            RenameEntrySyncJob::PAYLOAD_KEY_ID => $uuid,
            RenameEntrySyncJob::PAYLOAD_KEY_TITLE => $title,
            RenameEntrySyncJob::PAYLOAD_UPDATED_AT => UpdatedAt::now()->toRaw(),
        ])->handle();
    }


    /**
     * @throws PersistenceException
     */
    public function deleteEntrySync(string $uuid): Entry
    {

        return new DeleteEntrySyncJob([
            DeleteEntrySyncJob::PAYLOAD_KEY_ID => $uuid,
        ])->handle();
    }


    /**
     * @throws JwtTokenExpiredException
     * @throws JwtInvalidTokenException
     * @throws AuthException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function moveEntrySync(string $uuid, string $targetUuid, string $authUserMasterPassword): Entry
    {
        $authUser = $this->getAuthUser();
        $entry = $this->entryRepository->findById(EntryId::fromRaw($uuid));
        if (!$entry) {
            throw new InvalidArgumentException("Entry with id $uuid not found");
        }
        $entryGroup = $this->entryGroupRepository->findById($entry->entryGroupId);
        if (!$entryGroup) {
            throw new InvalidArgumentException("Entry Group of Entry with id $uuid not found");
        }
        $entryGroupTarget = $this->entryGroupRepository->findById(EntryGroupId::fromRaw($targetUuid));
        if (!$entryGroupTarget) {
            throw new InvalidArgumentException("Entry Group with id $targetUuid not found");
        }
        $entryGroupUser = $this->entryGroupUserRepository->findByUserIdAndEntryGroupId($authUser->id, $entryGroup->id);
        if (!$entryGroupUser) {
            throw new InvalidArgumentException("User is not in Origin Entry Group with id $entryGroup->id");
        }
        $entryGroupUserTarget = $this->entryGroupUserRepository->findByUserIdAndEntryGroupId($authUser->id, $entryGroupTarget->id);
        if (!$entryGroupUserTarget) {
            throw new InvalidArgumentException("User is not in Target Entry Group with id $entryGroupTarget->id");
        }

        $authUserPrivateKey = $this->rsaDomainService->getUserPrivateKey(
            userId: $authUser->id,
            masterPassword: $authUserMasterPassword,
        );

        $originAesPassword = $this->rsaDomainService->decryptByPrivate(
            data: $entryGroupUser->encryptedAesPassword->toRaw(),
            privateKey: $authUserPrivateKey,
        );

        $targetAesPassword = $this->rsaDomainService->decryptByPrivate(
            data: $entryGroupUserTarget->encryptedAesPassword->toRaw(),
            privateKey: $authUserPrivateKey,
        );


        return new MoveEntrySyncJob([
            MoveEntrySyncJob::PAYLOAD_KEY_ID => $uuid,
            MoveEntrySyncJob::PAYLOAD_KEY_ENTRY_GROUP_TARGET_ID => $targetUuid,
            MoveEntrySyncJob::PAYLOAD_KEY_ENTRY_GROUP_ORIGIN_AES_PASSWORD => $originAesPassword,
            MoveEntrySyncJob::PAYLOAD_KEY_ENTRY_GROUP_TARGET_AES_PASSWORD => $targetAesPassword,
            MoveEntrySyncJob::PAYLOAD_UPDATED_AT => UpdatedAt::now()->toRaw(),
        ])->handle();
    }

    /**
     * @param QueryCriteria[] $criteria
     *
     * @throws PersistenceException
     */
    public function search(string $query, ?array $criteria = [], ?array $orderBy = null, ?int $limit = null): array
    {
        $query = trim($query);
        $queryParts = explode(' ', $query)
                |> (fn($x) => array_map('trim', $x))
                |> array_filter(...);
        $query = count($queryParts) > 0
            ? '%' . implode('%', $queryParts) . '%'
            : '';

        return $this->entryRepository->findBy(
            criteria: [
                ...$criteria ?? [],
                new QueryCriteria(
                    field: EntryRepository::FIELD_TITLE,
                    value: $query,
                    operator: QueryOperatorEnum::LIKE,
                ),
            ],
            orderBy: $orderBy,
            limit: $limit,
        );
    }

    /**
     * @throws PersistenceException
     */
    public function getEntryByUuid(string $uuid): ?Entry
    {
        return $this->entryRepository->findById(EntryId::fromRaw($uuid));
    }

    /**
     * @param  QueryCriteria[]      $criteria
     * @throws PersistenceException
     * @return Entry[]
     */
    public function getEntryBy(
        array  $criteria,
        ?array $orderBy = null,
        ?int   $limit = null,
        ?int   $offset = null,
    ): array {
        return $this->entryRepository->findBy(
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
    public function countEntryBy(array $criteria = []): int
    {
        return $this->entryRepository->count($criteria);
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

}
