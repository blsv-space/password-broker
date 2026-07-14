<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryFieldHistory\Service;

use App\Module\Identity\Application\User\Service\Exception\AuthException;
use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\PasswordBroker\Application\EntryField\Service\Exception\EntryFieldException;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Service\EntryFieldHistoryApplicationService;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\AuthUserNotInEntryGroupException;
use App\Module\PasswordBroker\Domain\Entry\Entity\Entry;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\EntryFieldHistoryPassword;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Enum\RoleEnum;
use App\Shared\Domain\Security\Encryption\Exception\DecryptionException;
use App\Shared\Domain\Security\Encryption\Exception\EncryptionException;
use App\Shared\Infrastructure\Security\Encryption\AesEncryptor;
use App\Shared\Infrastructure\Security\Encryption\InitialVectorProvider;
use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;
use App\Shared\Infrastructure\Security\Exception\JwtTokenExpiredException;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use ReflectionException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFieldFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFieldHistoryFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupUserFixture;
use Tests\Shared\IntegrationTestCase;

class EntryFieldHistoryApplicationServiceTest extends IntegrationTestCase
{
    private User $authUser;
    private EntryFieldHistoryApplicationService $entryFieldHistoryApplicationService;


    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     */
    #[\Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->authUser = UserFixture::create(persist: true);
        $this->entryFieldHistoryApplicationService = EntryFieldHistoryApplicationService::getInstance();
        $this->actAs($this->authUser);
    }

    /**
     * @throws AuthException
     * @throws AuthUserNotInEntryGroupException
     * @throws EncryptionException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     * @throws EntryFieldException
     * @throws DecryptionException
     */
    public function test_it_should_decrypt_entry_field_history(): void
    {
        $aesEncryptor = AesEncryptor::getInstance();
        $initialVectorProvider = InitialVectorProvider::getInstance();

        $entry = $this->createAnEntry();
        $value = $this->faker->word;
        $iv = $initialVectorProvider->getInitialVector();
        $valueEncrypted = $aesEncryptor->encrypt(
            data: $value,
            password: EntryGroupUserFixture::DEFAULT_AES_PASSWORD,
            iv: $iv,
        );

        $entryField = EntryFieldFixture::create(
            attributes: [
                EntryFieldFixture::ENTRY => $entry,
                EntryFieldFixture::TYPE => EntryFieldTypeEnum::PASSWORD,
                EntryFieldFixture::VALUE_ENCRYPTED => $valueEncrypted->encryptedData,
                EntryFieldFixture::INITIALIZATION_VECTOR => $iv,
                EntryFieldFixture::TAG => $valueEncrypted->tag,
            ],
            persist: true,
        );

        $entryFieldHistory = EntryFieldHistoryFixture::create(
            attributes: [
                EntryFieldHistoryFixture::ENTRY_FIELD => $entryField,
            ],
            persist: true,
        );
        $this->assertInstanceOf(EntryFieldHistoryPassword::class, $entryFieldHistory);

        $decryptEntryField = $this->entryFieldHistoryApplicationService->decryptEntryFieldHistory(
            entryFieldHistory: $entryFieldHistory,
            masterPassword: UserFixture::DEFAULT_MASTER_PASSWORD,
        );

        $this->assertEquals($value, $decryptEntryField);
    }

    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_get_entry_field_history_by_uuid(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(
            attributes: [EntryFieldFixture::ENTRY => $entry],
            persist: true,
        );
        $entryFieldHistory = EntryFieldHistoryFixture::create(
            attributes: [EntryFieldHistoryFixture::ENTRY_FIELD => $entryField],
            persist: true,
        );
        $result = $this->entryFieldHistoryApplicationService->getEntryFieldHistoryByUuid($entryFieldHistory->getId()->toRaw());
        $this->assertEquals($entryFieldHistory->getId(), $result->getId());
    }

    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_get_entry_field_histories_by_criteria(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(
            attributes: [EntryFieldFixture::ENTRY => $entry],
            persist: true,
        );

        $entryFieldHistory = EntryFieldHistoryFixture::create(
            attributes: [EntryFieldHistoryFixture::ENTRY_FIELD => $entryField],
            persist: true,
        );
        $result = $this->entryFieldHistoryApplicationService->getEntryFieldHistoriesBy(criteria: [
            new QueryCriteria(
                field: EntryFieldHistoryFixture::ENTRY_FIELD_ID,
                value: $entryField->getId()->toRaw(),
            ),
            new QueryCriteria(
                field: EntryFieldHistoryFixture::TITLE,
                value: $entryFieldHistory->title->toRaw(),
            ),
        ]);
        $this->assertCount(1, $result);
        $this->assertEquals($entryFieldHistory->getId(), $result[0]->getId());
    }

    /**
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_count_entry_field_histories_by_criteria(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(attributes: [EntryFieldFixture::ENTRY => $entry], persist: true);

        EntryFieldHistoryFixture::create(
            attributes: [EntryFieldHistoryFixture::ENTRY_FIELD => $entryField],
            persist: true,
        );
        EntryFieldHistoryFixture::create(
            attributes: [EntryFieldHistoryFixture::ENTRY_FIELD => $entryField],
            persist: true,
        );

        $result = $this->entryFieldHistoryApplicationService->countEntryFieldHistoriesBy(criteria: [
            new QueryCriteria(
                field: EntryFieldHistoryFixture::ENTRY_FIELD_ID,
                value: $entryField->getId()->toRaw(),
            ),
        ]);
        $this->assertEquals(2, $result);
    }

    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    private function createAnEntry(): Entry
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $this->authUser->getId()->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->getId()->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::MEMBER->value,
            ],
            persist: true,
        );
        return EntryFixture::create(attributes: [EntryFixture::ENTRY_GROUP => $entryGroup], persist: true);
    }
}
