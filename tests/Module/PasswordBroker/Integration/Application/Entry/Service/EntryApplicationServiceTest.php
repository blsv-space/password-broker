<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\Entry\Service;

use App\Module\Identity\Application\User\Service\Exception\AuthException;
use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\Identity\Domain\User\Service\RsaDomainService;
use App\Module\PasswordBroker\Application\Entry\Service\EntryApplicationService;
use App\Module\PasswordBroker\Domain\Entry\Entity\Entry;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldId;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Enum\RoleEnum;
use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use App\Shared\Domain\Security\Encryption\Exception\DecryptionException;
use App\Shared\Domain\Security\Encryption\Exception\EncryptionException;
use App\Shared\Infrastructure\Security\Encryption\AesDecryptor;
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
use Throwable;

class EntryApplicationServiceTest extends IntegrationTestCase
{
    private User $authUser;
    private EntryApplicationService $entryApplicationService;

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
        $this->entryApplicationService = EntryApplicationService::getInstance();
        $this->actAs($this->authUser);
    }

    /**
     * @throws Throwable
     * @throws PersistenceException
     */
    public function test_it_should_create_entry(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        $title = $this->faker->word();

        $this->entryApplicationService->createEntrySync(
            title: $title,
            entryGroup: $entryGroup,
        );

        $this->assertDatabaseHas(
            table: EntryFixture::getTableName(),
            param: [
                EntryFixture::TITLE => $title,
                EntryFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            ],
        );
    }

    /**
     * @throws Throwable
     * @throws PersistenceException
     */
    public function test_it_should_create_entry_from_primitives(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        $title = $this->faker->word();

        $this->entryApplicationService->createEntryFromPrimitivesSync(
            title: $title,
            entryGroupId: $entryGroup->id->toRaw(),
        );

        $this->assertDatabaseHas(
            table: EntryFixture::getTableName(),
            param: [
                EntryFixture::TITLE => $title,
                EntryFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            ],
        );
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_rename_entry(): void
    {
        $entry = EntryFixture::create(persist: true);
        $newTitle = $this->faker->word();

        $this->entryApplicationService->renameEntrySync(
            uuid: $entry->id->toRaw(),
            title: $newTitle,
        );

        $this->assertDatabaseHas(
            table: EntryFixture::getTableName(),
            param: [
                EntryFixture::ID => $entry->id->toRaw(),
                EntryFixture::TITLE => $newTitle,
            ],
        );
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_delete_entry(): void
    {
        $entry = EntryFixture::create(persist: true);
        $this->entryApplicationService->deleteEntrySync(
            uuid: $entry->id->toRaw(),
        );
        $this->assertDatabaseMissing(
            table: EntryFixture::getTableName(),
            param: [
                EntryFixture::ID => $entry->id->toRaw(),
                EntryFixture::DELETED_AT => null,
            ],
        );
    }

    /**
     * @throws AuthException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     * @throws DecryptionException
     * @throws EncryptionException
     */
    public function test_it_should_move_entry_to_another_group(): void
    {
        $rsaDomainService = RsaDomainService::getInstance();
        $aesEncryptor = AesEncryptor::getInstance();
        $aesDecryptor = AesDecryptor::getInstance();
        $initialVectorProvider = InitialVectorProvider::getInstance();
        $entryGroupSource = EntryGroupFixture::create(persist: true);
        $entryGroupTarget = EntryGroupFixture::create(persist: true);

        $sourceAesPassword = $this->faker->word();
        $targetAesPassword = $this->faker->word();

        $userPublicKey = $rsaDomainService->getUserPublicKey(user: $this->authUser);

        $encryptedAesPasswordSource = $rsaDomainService->encryptByPublic(
            data: $sourceAesPassword,
            publicKey: $userPublicKey,
        );
        $encryptedAesPasswordTarget = $rsaDomainService->encryptByPublic(
            data: $targetAesPassword,
            publicKey: $userPublicKey,
        );

        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $this->authUser->getId()->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupSource->getId()->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::ADMIN->value,
                EntryGroupUserFixture::ENCRYPTED_AES_PASSWORD => $encryptedAesPasswordSource,
            ],
            persist: true,
        );
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $this->authUser->getId()->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupTarget->getId()->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::ADMIN->value,
                EntryGroupUserFixture::ENCRYPTED_AES_PASSWORD => $encryptedAesPasswordTarget,
            ],
            persist: true,
        );

        $entry = EntryFixture::create(
            attributes: [
                EntryFixture::ENTRY_GROUP => $entryGroupSource,
            ],
            persist: true,
        );

        $id_1 = EntryFieldId::generate()->toRaw();
        $value_1 = $this->faker->word();
        $iv_1 = $initialVectorProvider->getInitialVector();
        $value_encrypted_source_1 = $aesEncryptor->encrypt(
            data: $value_1,
            password: $sourceAesPassword,
            iv: $iv_1,
        );

        $id_2 = EntryFieldId::generate()->toRaw();
        $value_2 = $this->faker->word();
        $iv_2 = $initialVectorProvider->getInitialVector();
        $value_encrypted_source_2 = $aesEncryptor->encrypt(
            data: $value_2,
            password: $sourceAesPassword,
            iv: $iv_2,
        );

        $id_3 = EntryFieldId::generate()->toRaw();
        $value_3 = $this->faker->word();
        $iv_3 = $initialVectorProvider->getInitialVector();
        $value_encrypted_source_3 = $aesEncryptor->encrypt(
            data: $value_3,
            password: $sourceAesPassword,
            iv: $iv_3,
        );

        $idToValue = [
            $id_1 => $value_1,
            $id_2 => $value_2,
            $id_3 => $value_3,
        ];


        EntryFieldFixture::create(
            attributes: [
                EntryFieldFixture::ID => $id_1,
                EntryFieldFixture::ENTRY => $entry,
                EntryFieldFixture::VALUE_ENCRYPTED => $value_encrypted_source_1->encryptedData,
                EntryFieldFixture::INITIALIZATION_VECTOR => $iv_1,
                EntryFieldFixture::TAG => $value_encrypted_source_1->tag,
            ],
            persist: true,
        );
        EntryFieldFixture::create(
            attributes: [
                EntryFieldFixture::ID => $id_2,
                EntryFieldFixture::ENTRY => $entry,
                EntryFieldFixture::VALUE_ENCRYPTED => $value_encrypted_source_2->encryptedData,
                EntryFieldFixture::INITIALIZATION_VECTOR => $iv_2,
                EntryFieldFixture::TAG => $value_encrypted_source_2->tag,
            ],
            persist: true,
        );
        EntryFieldFixture::create(
            attributes: [
                EntryFieldFixture::ID => $id_3,
                EntryFieldFixture::ENTRY => $entry,
                EntryFieldFixture::VALUE_ENCRYPTED => $value_encrypted_source_3->encryptedData,
                EntryFieldFixture::INITIALIZATION_VECTOR => $iv_3,
                EntryFieldFixture::TAG => $value_encrypted_source_3->tag,
            ],
            persist: true,
        );

        $this->entryApplicationService->moveEntrySync(
            uuid: $entry->getId()->toRaw(),
            targetUuid: $entryGroupTarget->getId()->toRaw(),
            authUserMasterPassword: UserFixture::DEFAULT_MASTER_PASSWORD,
        );

        $this->assertDatabaseHas(
            table: EntryFixture::getTableName(),
            param: [
                EntryFixture::ID => $entry->getId()->toRaw(),
                EntryFixture::ENTRY_GROUP_ID => $entryGroupTarget->getId()->toRaw(),
            ],
        );

        $entryFieldEntries = EntryFieldRepository::getInstance()->findBy(
            [
                new QueryCriteria(
                    field: EntryFieldFixture::ENTRY_ID,
                    value: $entry->getId()->toRaw(),
                ),
            ],
        );

        $this->assertCount(3, $entryFieldEntries);

        foreach ($entryFieldEntries as $entryFieldEntry) {
            $this->assertArrayHasKey($entryFieldEntry->id->toRaw(), $idToValue);
            $this->assertEquals(
                $idToValue[$entryFieldEntry->id->toRaw()],
                $aesDecryptor->decrypt(
                    cipherText: $entryFieldEntry->valueEncrypted->toRaw(),
                    password: $targetAesPassword,
                    iv: $entryFieldEntry->initializationVector->toRaw(),
                    tag: $entryFieldEntry->tag->toRaw(),
                ),
            );
        }
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_search_entries(): void
    {
        $search = 'search_213 name';

        $nameA = $search . 'A';
        $nameB = $search . 'B';
        $nameC = $search . 'C';

        $shouldBeFoundInGroup = [$nameA, $nameB];
        $shouldBeFound = [$nameA, $nameB, $nameC];

        $nameD = 'nope';

        $entryGroup = EntryGroupFixture::create(persist: true);
        EntryFixture::create(
            attributes: [
                EntryFixture::ENTRY_GROUP => $entryGroup,
                EntryFixture::TITLE => $nameA,
            ],
            persist: true,
        );
        EntryFixture::create(
            attributes: [
                EntryFixture::ENTRY_GROUP => $entryGroup,
                EntryFixture::TITLE => $nameB,
            ],
            persist: true,
        );
        EntryFixture::create(
            attributes: [
                EntryFixture::TITLE => $nameC,
            ],
            persist: true,
        );
        EntryFixture::create(
            attributes: [
                EntryFixture::ENTRY_GROUP => $entryGroup,
                EntryFixture::TITLE => $nameD,
            ],
            persist: true,
        );

        $result = $this->entryApplicationService->search(query: $search);

        $this->assertCount(3, $result);
        $foundNames = array_map(fn(Entry $entry) => $entry->title->toRaw(), $result);
        sort($foundNames);
        sort($shouldBeFound);
        $this->assertEquals($shouldBeFound, $foundNames);

        $result = $this->entryApplicationService->search(query: $search, criteria: [
            new QueryCriteria(
                field: EntryFixture::ENTRY_GROUP_ID,
                value: $entryGroup->id->toRaw(),
            ),
        ]);

        $this->assertCount(2, $result);
        $foundNames = array_map(fn(Entry $entry) => $entry->title->toRaw(), $result);
        sort($foundNames);
        sort($shouldBeFoundInGroup);
        $this->assertEquals($shouldBeFoundInGroup, $foundNames);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_get_entry_by_uuid(): void
    {
        $entry = EntryFixture::create(persist: true);
        $result = $this->entryApplicationService->getEntryByUuid($entry->id->toRaw());
        $this->assertEquals($entry->id, $result->id);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_get_entry_by_criteria(): void
    {
        $entry = EntryFixture::create(persist: true);
        $result = $this->entryApplicationService->getEntryBy(criteria: [
            new QueryCriteria(
                field: EntryFixture::ENTRY_GROUP_ID,
                value: $entry->entryGroupId->toRaw(),
            ),
        ]);

        $this->assertCount(1, $result);
        $this->assertEquals($entry->id, $result[0]->id);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_count_entries_by_criteria(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);

        EntryFixture::createMany(
            count: 5,
            attributes: [
                EntryFixture::ENTRY_GROUP => $entryGroup,
            ],
        );

        EntryFixture::createMany(count: 2);

        $result = $this->entryApplicationService->countEntryBy(criteria: [
            new QueryCriteria(
                field: EntryFixture::ENTRY_GROUP_ID,
                value: $entryGroup->id->toRaw(),
            ),
        ]);

        $this->assertEquals(5, $result);
    }
}
