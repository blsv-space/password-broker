<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Service;

use App\Module\Identity\Application\User\Service\AuthApplicationService;
use App\Module\Identity\Application\User\Service\Exception\AuthException;
use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\Identity\Domain\User\Service\RsaDomainService;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldDecryptedEvent;
use App\Module\PasswordBroker\Application\EntryField\Job\AbstractCreateEntryFieldSyncJob;
use App\Module\PasswordBroker\Application\EntryField\Job\AbstractUpdateEntryFieldSyncJob;
use App\Module\PasswordBroker\Application\EntryField\Job\CreateEntryFieldFileSyncJob;
use App\Module\PasswordBroker\Application\EntryField\Job\CreateEntryFieldLinkSyncJob;
use App\Module\PasswordBroker\Application\EntryField\Job\CreateEntryFieldNoteSyncJob;
use App\Module\PasswordBroker\Application\EntryField\Job\CreateEntryFieldPasswordSyncJob;
use App\Module\PasswordBroker\Application\EntryField\Job\CreateEntryFieldTotpSyncJob;
use App\Module\PasswordBroker\Application\EntryField\Job\DeleteEntryFieldSyncJob;
use App\Module\PasswordBroker\Application\EntryField\Job\UpdateEntryFieldFileSyncJob;
use App\Module\PasswordBroker\Application\EntryField\Job\UpdateEntryFieldLinkSyncJob;
use App\Module\PasswordBroker\Application\EntryField\Job\UpdateEntryFieldNoteSyncJob;
use App\Module\PasswordBroker\Application\EntryField\Job\UpdateEntryFieldPasswordSyncJob;
use App\Module\PasswordBroker\Application\EntryField\Job\UpdateEntryFieldTotpSyncJob;
use App\Module\PasswordBroker\Application\EntryField\Service\Exception\EntryFieldException;
use App\Module\PasswordBroker\Application\EntryField\Service\Exception\EntryFieldNotFountException;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\AuthUserNotInEntryGroupException;
use App\Module\PasswordBroker\Domain\Entry\Repository\EntryRepositoryInterface;
use App\Module\PasswordBroker\Domain\Entry\ValueObject\EntryId;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use App\Module\PasswordBroker\Domain\EntryField\Repository\EntryFieldRepositoryInterface;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldId;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Repository\EntryGroupUserRepositoryInterface;
use App\Module\PasswordBroker\Infrastructure\Entry\Repository\EntryRepository;
use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use App\Module\PasswordBroker\Infrastructure\EntryGroupUser\Repository\EntryGroupUserRepository;
use App\Shared\Domain\Security\Encryption\Exception\DecryptionException;
use App\Shared\Domain\Security\Encryption\Exception\EncryptionException;
use App\Shared\Domain\ValueObject\CreatedAt;
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

class EntryFieldApplicationService implements ApplicationServiceInterface
{
    use SingletonTrait;

    private EntryFieldRepositoryInterface $entryFieldRepository;
    private EntryRepositoryInterface $entryRepository;
    private RsaDomainService $rsaDomainService;
    private EntryGroupUserRepositoryInterface $entryGroupUserRepository;

    private function __construct()
    {
        $this->entryFieldRepository = EntryFieldRepository::getInstance();
        $this->entryRepository = EntryRepository::getInstance();
        $this->rsaDomainService = RsaDomainService::getInstance();
        $this->entryGroupUserRepository = EntryGroupUserRepository::getInstance();
    }

    /**
     * @throws AuthUserNotInEntryGroupException
     * @throws JwtTokenExpiredException
     * @throws EncryptionException
     * @throws JwtInvalidTokenException
     * @throws AuthException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function createEntryFieldFromPrimitivesSync(
        string  $entryId,
        string  $type,
        string  $title,
        string  $value,
        string  $masterPassword,
        ?string $fileName = null,
        ?string $fileMime = null,
        ?int    $fileSize = null,
        ?string $login = null,
        ?string $totpHashAlgorithm = null,
        ?int $totpTimeout = null,
    ): AbstractEntryField {
        $authUser = $this->getAuthUser();
        $entryGroupAesPassword = $this->getAesPassword($entryId, $authUser, $masterPassword);
        $initialVector = InitialVectorProvider::getInstance()->getInitialVector();
        $aesEncryptedData = AesEncryptor::getInstance()->encrypt($value, $entryGroupAesPassword, $initialVector);

        $payload = [
            AbstractCreateEntryFieldSyncJob::PAYLOAD_KEY_ID => EntryFieldId::generate()->toRaw(),
            AbstractCreateEntryFieldSyncJob::PAYLOAD_KEY_ENTRY_ID => $entryId,
            AbstractCreateEntryFieldSyncJob::PAYLOAD_KEY_FIELD_TYPE => $type,
            AbstractCreateEntryFieldSyncJob::PAYLOAD_KEY_TITLE => $title,
            AbstractCreateEntryFieldSyncJob::PAYLOAD_KEY_VALUE_ENCRYPTED => $aesEncryptedData->encryptedData,
            AbstractCreateEntryFieldSyncJob::PAYLOAD_KEY_INITIALIZATION_VECTOR => $initialVector,
            AbstractCreateEntryFieldSyncJob::PAYLOAD_KEY_TAG => $aesEncryptedData->tag,
            AbstractCreateEntryFieldSyncJob::PAYLOAD_KEY_CREATED_AT => CreatedAt::now()->toRaw(),
            AbstractCreateEntryFieldSyncJob::PAYLOAD_KEY_CREATED_BY => $authUser->id->toRaw(),
            AbstractCreateEntryFieldSyncJob::PAYLOAD_EXECUTED_BY => $authUser->id->toRaw(),
        ];

        return match ($type) {
            default => throw new RuntimeException('Unsupported field type'),
            EntryFieldTypeEnum::FILE->value => new CreateEntryFieldFileSyncJob(
                payload: array_merge(
                    $payload,
                    [
                        CreateEntryFieldFileSyncJob::PAYLOAD_KEY_FILE_NAME => $fileName,
                        CreateEntryFieldFileSyncJob::PAYLOAD_KEY_FILE_MIME => $fileMime,
                        CreateEntryFieldFileSyncJob::PAYLOAD_KEY_FILE_SIZE => $fileSize,
                    ],
                ),
            )->handle(),
            EntryFieldTypeEnum::LINK->value => new CreateEntryFieldLinkSyncJob(
                payload: $payload,
            )->handle(),
            EntryFieldTypeEnum::NOTE->value => new CreateEntryFieldNoteSyncJob(
                payload: $payload,
            )->handle(),
            EntryFieldTypeEnum::PASSWORD->value => new CreateEntryFieldPasswordSyncJob(
                payload: array_merge(
                    $payload,
                    [
                        CreateEntryFieldPasswordSyncJob::PAYLOAD_KEY_LOGIN => $login,
                    ],
                ),
            )->handle(),
            EntryFieldTypeEnum::TOTP->value => new CreateEntryFieldTotpSyncJob(
                payload: array_merge(
                    $payload,
                    [
                        CreateEntryFieldTotpSyncJob::PAYLOAD_KEY_TOTP_HASH_ALGORITHM => $totpHashAlgorithm,
                        CreateEntryFieldTotpSyncJob::PAYLOAD_KEY_TOTP_TIMEOUT => $totpTimeout,
                    ],
                ),
            )->handle(),
        };
    }

    /**
     * @throws AuthUserNotInEntryGroupException
     * @throws JwtTokenExpiredException
     * @throws EncryptionException
     * @throws JwtInvalidTokenException
     * @throws AuthException
     * @throws RsaDomainServiceException
     * @throws EntryFieldNotFountException
     * @throws PersistenceException
     */
    public function updateEntryFieldSync(
        string  $id,
        string  $title,
        ?string  $value = null,
        ?string  $masterPassword = null,
        ?string $fileName = null,
        ?string $fileMime = null,
        ?int    $fileSize = null,
        ?string $login = null,
        ?string $totpHashAlgorithm = null,
        ?int $totpTimeout = null,
    ): AbstractEntryField {
        $entryField = $this->entryFieldRepository->findById(EntryFieldId::fromRaw($id));
        if (!$entryField) {
            throw new EntryFieldNotFountException($id);
        }
        $authUser = $this->getAuthUser();

        $payload = [
            ...array_filter(
                array: $entryField->getAsArray(),
                callback: static fn($key) => !in_array(
                    $key,
                    [
                        AbstractUpdateEntryFieldSyncJob::PAYLOAD_KEY_VALUE_ENCRYPTED,
                        AbstractUpdateEntryFieldSyncJob::PAYLOAD_KEY_INITIALIZATION_VECTOR,
                        AbstractUpdateEntryFieldSyncJob::PAYLOAD_KEY_TAG,
                    ],
                ),
                mode: ARRAY_FILTER_USE_KEY,
            ),
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_KEY_ID => $entryField->id->toRaw(),
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_KEY_TITLE => $title,
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_KEY_UPDATED_AT => CreatedAt::now()->toRaw(),
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_KEY_UPDATED_BY => $authUser->id->toRaw(),
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_EXECUTED_BY => $authUser->id->toRaw(),
        ];

        if (!is_null($value)) {
            $entryGroupAesPassword = $this->getAesPassword($entryField->entryId->toRaw(), $authUser, $masterPassword);
            $initialVector = InitialVectorProvider::getInstance()->getInitialVector();
            $aesEncryptedData = AesEncryptor::getInstance()->encrypt($value, $entryGroupAesPassword, $initialVector);
            $payload[AbstractUpdateEntryFieldSyncJob::PAYLOAD_KEY_VALUE_ENCRYPTED] = $aesEncryptedData->encryptedData;
            $payload[AbstractUpdateEntryFieldSyncJob::PAYLOAD_KEY_INITIALIZATION_VECTOR] = $initialVector;
            $payload[AbstractUpdateEntryFieldSyncJob::PAYLOAD_KEY_TAG] = $aesEncryptedData->tag;
        }

        return match ($entryField->type->value) {
            default => throw new RuntimeException('Unsupported field type'),
            EntryFieldTypeEnum::FILE => new UpdateEntryFieldFileSyncJob(
                payload: array_merge(
                    $payload,
                    [
                        UpdateEntryFieldFileSyncJob::PAYLOAD_KEY_FILE_NAME => $fileName,
                        UpdateEntryFieldFileSyncJob::PAYLOAD_KEY_FILE_MIME => $fileMime,
                        UpdateEntryFieldFileSyncJob::PAYLOAD_KEY_FILE_SIZE => $fileSize,
                    ],
                ),
            )->handle(),
            EntryFieldTypeEnum::LINK => new UpdateEntryFieldLinkSyncJob(
                payload: $payload,
            )->handle(),
            EntryFieldTypeEnum::NOTE => new UpdateEntryFieldNoteSyncJob(
                payload: $payload,
            )->handle(),
            EntryFieldTypeEnum::PASSWORD => new UpdateEntryFieldPasswordSyncJob(
                payload: array_merge(
                    $payload,
                    [
                        UpdateEntryFieldPasswordSyncJob::PAYLOAD_KEY_LOGIN => $login
                            ?? $payload[UpdateEntryFieldPasswordSyncJob::PAYLOAD_KEY_LOGIN],
                    ],
                ),
            )->handle(),
            EntryFieldTypeEnum::TOTP => new UpdateEntryFieldTotpSyncJob(
                payload: array_merge(
                    $payload,
                    [
                        UpdateEntryFieldTotpSyncJob::PAYLOAD_KEY_TOTP_HASH_ALGORITHM => $totpHashAlgorithm
                            ?? $payload[UpdateEntryFieldTotpSyncJob::PAYLOAD_KEY_TOTP_HASH_ALGORITHM],
                        UpdateEntryFieldTotpSyncJob::PAYLOAD_KEY_TOTP_TIMEOUT => $totpTimeout
                            ?? $payload[UpdateEntryFieldTotpSyncJob::PAYLOAD_KEY_TOTP_TIMEOUT],
                    ],
                ),
            )->handle(),
        };
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
    public function decryptEntryField(AbstractEntryField $entryField, string $masterPassword): string
    {
        $entry = $this->entryRepository->findById($entryField->entryId);
        if (!$entry) {
            throw new EntryFieldException('Entry not found');
        }
        $authUser = $this->getAuthUser();
        $entryGroupAesPassword = $this->getAesPassword($entry->getId()->toRaw(), $authUser, $masterPassword);

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
     * @throws AuthException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws PersistenceException
     */
    public function deleteEntryFieldSync(string $uuid): AbstractEntryField
    {
        $authUser = $this->getAuthUser();

        return new DeleteEntryFieldSyncJob([
            DeleteEntryFieldSyncJob::PAYLOAD_KEY_ID => $uuid,
            DeleteEntryFieldSyncJob::PAYLOAD_EXECUTED_BY => $authUser->id->toRaw(),
        ])->handle();
    }

    /**
     * @throws PersistenceException
     */
    public function getEntryFieldByUuid(string $uuid): ?AbstractEntryField
    {
        return $this->entryFieldRepository->findById(EntryFieldId::fromRaw($uuid));
    }

    /**
     * @param  QueryCriteria[]      $criteria
     * @throws PersistenceException
     * @return AbstractEntryField[]
     */
    public function getEntryFieldsBy(
        array  $criteria,
        ?array $orderBy = null,
        ?int   $limit = null,
        ?int   $offset = null,
    ): array {
        return $this->entryFieldRepository->findBy(
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
    public function countEntryFieldsBy(array $criteria = []): int
    {
        return $this->entryFieldRepository->count($criteria);
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
     * @throws RsaDomainServiceException
     */
    public function getAesPassword(string $entryId, User $user, string $masterPassword): string
    {
        $entry = $this->entryRepository->findById(EntryId::fromRaw($entryId));

        if (!$entry) {
            throw new RuntimeException('Entry not found');
        }

        $entryGroupUser = $this->entryGroupUserRepository->findByUserIdAndEntryGroupId($user->id, $entry->entryGroupId);
        if (!$entryGroupUser) {
            throw new AuthUserNotInEntryGroupException();
        }
        $userPrivateKey = $this->rsaDomainService->getUserPrivateKey(
            userId: $user->id,
            masterPassword: $masterPassword,
        );

        return $this->rsaDomainService->decryptByPrivate(
            data: $entryGroupUser->encryptedAesPassword->toRaw(),
            privateKey: $userPrivateKey,
        );
    }
}
