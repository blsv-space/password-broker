<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryField\Service;

use App\Module\Identity\Application\User\Service\Exception\AuthException;
use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\PasswordBroker\Application\EntryField\Service\EntryFieldApplicationService;
use App\Module\PasswordBroker\Application\EntryField\Service\Exception\EntryFieldException;
use App\Module\PasswordBroker\Application\EntryField\Service\Exception\EntryFieldNotFountException;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\AuthUserNotInEntryGroupException;
use App\Module\PasswordBroker\Domain\Entry\Entity\Entry;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldPassword;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
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
use Tests\Module\PasswordBroker\Fixture\EntryFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupUserFixture;
use Tests\Shared\IntegrationTestCase;

class EntryFieldApplicationServiceTest extends IntegrationTestCase
{
    private User $authUser;
    private EntryFieldApplicationService $entryFieldApplicationService;

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
        $this->entryFieldApplicationService = EntryFieldApplicationService::getInstance();
        $this->actAs($this->authUser);
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
    public function test_it_should_create_entry_field_from_primitives(): void
    {
        $entry = $this->createAnEntry();

        $entryField = EntryFieldFixture::create(
            attributes: [
                EntryFieldFixture::ENTRY => $entry,
                EntryFieldFixture::TYPE => EntryFieldTypeEnum::PASSWORD,
            ],
        );
        $this->assertInstanceOf(EntryFieldPassword::class, $entryField);
        $value = $this->faker->word();

        $this->entryFieldApplicationService->createEntryFieldFromPrimitivesSync(
            entryId: $entryField->entryId->toRaw(),
            type: $entryField->type->toRaw(),
            title: $entryField->title->toRaw(),
            value: $value,
            masterPassword: UserFixture::DEFAULT_MASTER_PASSWORD,
            login: $entryField->login->toRaw(),
        );

        $this->assertDatabaseHas(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ENTRY_ID => $entryField->entryId->toRaw(),
            EntryFieldFixture::TYPE => $entryField->type->toRaw(),
            EntryFieldFixture::TITLE => $entryField->title->toRaw(),
            EntryFieldFixture::LOGIN => $entryField->login->toRaw(),
        ]);
    }


    /**
     * @throws AuthException
     * @throws AuthUserNotInEntryGroupException
     * @throws EncryptionException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     * @throws EntryFieldNotFountException
     */
    public function test_it_should_update_entry_field_sync(): void
    {
        $entry = $this->createAnEntry();
        $loginOrigin = $this->faker->email;
        $loginUpdated = $this->faker->email;

        $entryField = EntryFieldFixture::create(
            attributes: [
                EntryFieldFixture::ENTRY => $entry,
                EntryFieldFixture::TYPE => EntryFieldTypeEnum::PASSWORD,
                EntryFieldFixture::LOGIN => $loginOrigin,
            ],
            persist: true,
        );
        $this->assertInstanceOf(EntryFieldPassword::class, $entryField);

        $this->entryFieldApplicationService->updateEntryFieldSync(
            id: $entryField->getId()->toRaw(),
            title: $entryField->title->toRaw(),
            login: $loginUpdated,
        );

        $this->assertDatabaseHas(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ID => $entryField->getId()->toRaw(),
            EntryFieldFixture::LOGIN => $loginUpdated,
        ]);
        $this->assertDatabaseMissing(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ID => $entryField->getId()->toRaw(),
            EntryFieldFixture::LOGIN => $loginOrigin,
        ]);
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
    public function test_it_should_decrypt_entry_field(): void
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
        $this->assertInstanceOf(EntryFieldPassword::class, $entryField);

        $decryptEntryField = $this->entryFieldApplicationService->decryptEntryField(
            entryField: $entryField,
            masterPassword: UserFixture::DEFAULT_MASTER_PASSWORD,
        );

        $this->assertEquals($value, $decryptEntryField);
    }

    /**
     * @throws AuthException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_delete_entry_field_sync(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(
            attributes: [EntryFieldFixture::ENTRY => $entry],
            persist: true,
        );
        $this->assertDatabaseHas(
            table: EntryFieldFixture::getTableName(),
            param: [
                EntryFieldFixture::ID => $entryField->getId()->toRaw(),
                EntryFieldFixture::DELETED_AT => null,
            ],
        );

        $this->entryFieldApplicationService->deleteEntryFieldSync(
            uuid: $entryField->getId()->toRaw(),
        );

        $this->assertDatabaseHas(
            table: EntryFieldFixture::getTableName(),
            param: [
                EntryFieldFixture::ID => $entryField->getId()->toRaw(),
            ],
        );

        $this->assertDatabaseMissing(
            table: EntryFieldFixture::getTableName(),
            param: [
                EntryFieldFixture::ID => $entryField->getId()->toRaw(),
                EntryFieldFixture::DELETED_AT => null,
            ],
        );
    }

    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_get_entry_field_by_uuid(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(
            attributes: [EntryFieldFixture::ENTRY => $entry],
            persist: true,
        );
        $result = $this->entryFieldApplicationService->getEntryFieldByUuid($entryField->getId()->toRaw());
        $this->assertEquals($entryField->getId(), $result->getId());
    }

    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_get_entry_fields_by_criteria(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(
            attributes: [EntryFieldFixture::ENTRY => $entry],
            persist: true,
        );
        $result = $this->entryFieldApplicationService->getEntryFieldsBy(criteria: [
            new QueryCriteria(
                field: EntryFieldFixture::ENTRY_ID,
                value: $entry->getId()->toRaw(),
            ),
            new QueryCriteria(
                field: EntryFieldFixture::TITLE,
                value: $entryField->title->toRaw(),
            ),
        ]);
        $this->assertCount(1, $result);
        $this->assertEquals($entryField->getId(), $result[0]->getId());
    }

    /**
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_count_entry_fields_by_criteria(): void
    {
        $entry = $this->createAnEntry();
        EntryFieldFixture::create(attributes: [EntryFieldFixture::ENTRY => $entry], persist: true);
        EntryFieldFixture::create(attributes: [EntryFieldFixture::ENTRY => $entry], persist: true);
        $result = $this->entryFieldApplicationService->countEntryFieldsBy(criteria: [
            new QueryCriteria(
                field: EntryFieldFixture::ENTRY_ID,
                value: $entry->getId()->toRaw(),
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
