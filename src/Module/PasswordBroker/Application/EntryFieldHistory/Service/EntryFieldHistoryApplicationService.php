<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\Service;

use App\Module\Identity\Application\User\Service\AuthApplicationService;
use App\Module\Identity\Application\User\Service\Exception\AuthException;
use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\Identity\Domain\User\Service\RsaDomainService;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldDecryptedEvent;
use App\Module\PasswordBroker\Application\EntryField\Service\EntryFieldApplicationService;
use App\Module\PasswordBroker\Application\EntryField\Service\Exception\EntryFieldException;
use App\Module\PasswordBroker\Application\EntryField\Service\Exception\EntryFieldNotFountException;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Job\AbstractUpdateEntryFieldHistorySyncJob;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Job\CreateEntryFieldHistoryLinkSyncJob;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Job\CreateEntryFieldHistoryNoteSyncJob;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Job\CreateEntryFieldHistoryPasswordSyncJob;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Job\CreateEntryFieldHistoryTotpSyncJob;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Job\UpdateEntryFieldHistoryEncryptedValueSyncJob;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Service\Exception\EntryFieldHistoryNotFountException;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\AuthUserNotInEntryGroupException;
use App\Module\PasswordBroker\Domain\Entry\Repository\EntryRepositoryInterface;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
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
use App\Shared\Domain\Security\Encryption\Exception\EncryptionException;
use App\Shared\Infrastructure\Security\Encryption\AesDecryptor;
use App\Shared\Infrastructure\Security\Encryption\AesEncryptor;
use App\Shared\Infrastructure\Security\Encryption\InitialVectorProvider;
use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;
use App\Shared\Infrastructure\Security\Exception\JwtTokenExpiredException;
use Inquisition\Core\Application\Service\ApplicationServiceInterface;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use Inquisition\Foundation\Singleton\SingletonTrait;
use RuntimeException;

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
     * @throws PersistenceException
     */
    public function createEntryFieldHistoryFromEntryFieldSync(
        AbstractEntryField $entryField,
    ): AbstractEntryFieldHistory {

        return match ($entryField->type->toRaw()) {
            default => throw new RuntimeException('Unsupported field type'),
            EntryFieldTypeEnum::LINK->value => new CreateEntryFieldHistoryLinkSyncJob(
                payload: $entryField->getAsArray(),
            )->handle(),
            EntryFieldTypeEnum::NOTE->value => new CreateEntryFieldHistoryNoteSyncJob(
                payload: $entryField->getAsArray(),
            )->handle(),
            EntryFieldTypeEnum::PASSWORD->value => new CreateEntryFieldHistoryPasswordSyncJob(
                payload: $entryField->getAsArray(),
            )->handle(),
            EntryFieldTypeEnum::TOTP->value => new CreateEntryFieldHistoryTotpSyncJob(
                payload: $entryField->getAsArray(),
            )->handle(),
        };
    }

    /**
     * @throws AuthException
     * @throws AuthUserNotInEntryGroupException
     * @throws EncryptionException
     * @throws EntryFieldHistoryNotFountException
     * @throws EntryFieldNotFountException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function updateEntryFieldHistoryEncryptedValueSync(
        string  $id,
        string  $value,
        string  $masterPassword,
    ): AbstractEntryFieldHistory {
        $entryFieldHistory = $this->entryFieldHistoryRepository->findById(EntryFieldHistoryId::fromRaw($id));
        if (!$entryFieldHistory) {
            throw new EntryFieldHistoryNotFountException($id);
        }
        $entryField = $this->entryFieldApplicationService->getEntryFieldByUuid($entryFieldHistory->entryFieldId->toRaw());
        if (!$entryField) {
            throw new EntryFieldNotFountException($entryFieldHistory->entryFieldId->toRaw());
        }

        $authUser = $this->getAuthUser();

        $entryGroupAesPassword = $this->entryFieldApplicationService->getAesPassword($entryField->entryId->toRaw(), $authUser, $masterPassword);
        $initialVector = InitialVectorProvider::getInstance()->getInitialVector();
        $aesEncryptedData = AesEncryptor::getInstance()->encrypt($value, $entryGroupAesPassword, $initialVector);
        $payload = $entryField->getAsArray();
        $payload[AbstractUpdateEntryFieldHistorySyncJob::PAYLOAD_KEY_VALUE_ENCRYPTED] = $aesEncryptedData->encryptedData;
        $payload[AbstractUpdateEntryFieldHistorySyncJob::PAYLOAD_KEY_INITIALIZATION_VECTOR] = $initialVector;
        $payload[AbstractUpdateEntryFieldHistorySyncJob::PAYLOAD_KEY_TAG] = $aesEncryptedData->tag;

        return new UpdateEntryFieldHistoryEncryptedValueSyncJob(
            payload: $payload,
        )->handle();
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
            cipherText: $entryField->valueEncrypted->toRaw(),
            password: $entryGroupAesPassword,
            iv: $entryField->initializationVector->toRaw(),
            tag: $entryField->tag->toRaw(),
        );

        EventDispatcher::getInstance()->dispatch(
            new EntryFieldDecryptedEvent(
                entryField: $entryField,
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
