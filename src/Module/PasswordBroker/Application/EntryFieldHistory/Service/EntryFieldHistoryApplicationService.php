<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\Service;

use App\Module\Identity\Application\User\Service\AuthApplicationService;
use App\Module\Identity\Application\User\Service\Exception\AuthException;
use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\Identity\Domain\User\Service\RsaDomainService;
use App\Module\PasswordBroker\Application\EntryField\Service\EntryFieldApplicationService;
use App\Module\PasswordBroker\Application\EntryField\Service\Exception\EntryFieldException;
use App\Module\PasswordBroker\Application\EntryField\Service\Exception\EntryFieldNotFountException;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Event\EntryFieldHistoryDecryptedEvent;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\AuthUserNotInEntryGroupException;
use App\Module\PasswordBroker\Domain\Entry\Repository\EntryRepositoryInterface;
use App\Module\PasswordBroker\Domain\EntryField\Repository\EntryFieldRepositoryInterface;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\AbstractEntryFieldHistory;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Repository\EntryFieldHistoryRepositoryInterface;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\ValueObject\EntryFieldHistoryId;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Repository\EntryGroupUserRepositoryInterface;
use App\Module\PasswordBroker\Infrastructure\Entry\Repository\EntryRepository;
use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use App\Module\PasswordBroker\Infrastructure\EntryFieldHistory\Repository\EntryFieldHistoryRepository;
use App\Module\PasswordBroker\Infrastructure\EntryGroupUser\Repository\EntryGroupUserRepository;
use App\Shared\Domain\Security\Encryption\Exception\DecryptionException;
use App\Shared\Infrastructure\Security\Encryption\AesDecryptor;
use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;
use App\Shared\Infrastructure\Security\Exception\JwtTokenExpiredException;
use Inquisition\Core\Application\Service\ApplicationServiceInterface;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use Inquisition\Foundation\Singleton\SingletonTrait;

class EntryFieldHistoryApplicationService implements ApplicationServiceInterface
{
    use SingletonTrait;

    private EntryFieldHistoryRepositoryInterface $entryFieldHistoryRepository;
    private EntryFieldRepositoryInterface $entryFieldRepository;
    private EntryRepositoryInterface $entryRepository;
    private EntryGroupUserRepositoryInterface $entryGroupUserRepository;
    private RsaDomainService $rsaDomainService;
    private EntryFieldApplicationService $entryFieldApplicationService;

    private function __construct()
    {
        $this->entryFieldHistoryRepository = EntryFieldHistoryRepository::getInstance();
        $this->entryFieldRepository = EntryFieldRepository::getInstance();
        $this->entryRepository = EntryRepository::getInstance();
        $this->entryGroupUserRepository = EntryGroupUserRepository::getInstance();
        $this->rsaDomainService = RsaDomainService::getInstance();
        $this->entryFieldApplicationService = EntryFieldApplicationService::getInstance();
    }

    /**
     * @throws AuthUserNotInEntryGroupException
     * @throws EntryFieldException
     * @throws JwtTokenExpiredException
     * @throws JwtInvalidTokenException
     * @throws AuthException
     * @throws RsaDomainServiceException
     * @throws DecryptionException
     * @throws PersistenceException
     */
    public function decryptEntryFieldHistory(AbstractEntryFieldHistory $entryFieldHistory, string $masterPassword): string
    {
        $entryField = $this->entryFieldApplicationService->getEntryFieldByUuid($entryFieldHistory->entryFieldId->toRaw());
        if (!$entryField) {
            throw new EntryFieldNotFountException($entryFieldHistory->entryFieldId->toRaw());
        }
        $authUser = $this->getAuthUser();
        $entryGroupAesPassword = $this->entryFieldApplicationService->getAesPassword($entryField->entryId->toRaw(), $authUser, $masterPassword);

        $decryptedValue = AesDecryptor::getInstance()->decrypt(
            cipherText: $entryFieldHistory->valueEncrypted->toRaw(),
            password: $entryGroupAesPassword,
            iv: $entryFieldHistory->initializationVector->toRaw(),
            tag: $entryFieldHistory->tag->toRaw(),
        );

        EventDispatcher::getInstance()->dispatch(
            new EntryFieldHistoryDecryptedEvent(
                entryFieldHistory: $entryFieldHistory,
                executorId: $authUser->id->toRaw(),
            ),
        );

        return $decryptedValue;
    }

    /**
     * @throws PersistenceException
     */
    public function getEntryFieldHistoryByUuid(string $uuid): ?AbstractEntryFieldHistory
    {
        return $this->entryFieldHistoryRepository->findById(EntryFieldHistoryId::fromRaw($uuid));
    }

    /**
     * @param  QueryCriteria[]             $criteria
     * @throws PersistenceException
     * @return AbstractEntryFieldHistory[]
     */
    public function getEntryFieldHistoriesBy(
        array  $criteria,
        ?array $orderBy = null,
        ?int   $limit = null,
        ?int   $offset = null,
    ): array {
        return $this->entryFieldHistoryRepository->findBy(
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
    public function countEntryFieldHistoriesBy(array $criteria = []): int
    {
        return $this->entryFieldHistoryRepository->count($criteria);
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
