<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryGroupUser\Job;

use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\Identity\Domain\User\Service\RsaDomainService;
use App\Module\PasswordBroker\Application\EntryGroupUser\Event\EntryGroupUserCreatedEvent;
use App\Module\PasswordBroker\Application\EntryGroupUser\Job\AddUserToGroupSyncJob;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Enum\RoleEnum;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupUserFixture;
use Tests\Shared\IntegrationTestCase;
use Tests\Shared\TestEventHandler;

class AddUserToGroupSyncJobTest extends IntegrationTestCase
{
    /**
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_add_a_user_to_an_entry_group(): void
    {
        $user = UserFixture::create(persist: true);
        $entryGroup = EntryGroupFixture::create(persist: true);
        $rsaDomainService = RsaDomainService::getInstance();

        $targetUserPublicKey = $rsaDomainService->getUserPublicKey(user: $user);

        $entryGroupAesPasswordEncrypted = $rsaDomainService->encryptByPublic(
            data: EntryGroupUserFixture::DEFAULT_AES_PASSWORD,
            publicKey: $targetUserPublicKey,
        );
        $payload = [
            AddUserToGroupSyncJob::PAYLOAD_KEY_ID => EntryGroupId::generate()->toRaw(),
            AddUserToGroupSyncJob::PAYLOAD_KEY_USER_ID => $user->getId()->toRaw(),
            AddUserToGroupSyncJob::PAYLOAD_KEY_ENTRY_GROUP_ID => $entryGroup->getId()->toRaw(),
            AddUserToGroupSyncJob::PAYLOAD_KEY_ROLE => RoleEnum::MEMBER->value,
            AddUserToGroupSyncJob::PAYLOAD_KEY_ENCRYPTED_AES_PASSWORD => $entryGroupAesPasswordEncrypted,
            AddUserToGroupSyncJob::PAYLOAD_CREATED_AT => $this->faker->dateTime()->format('Y-m-d H:i:s'),
        ];

        new AddUserToGroupSyncJob($payload)->handle();

        $this->assertDatabaseHas(
            table: EntryGroupUserFixture::getTableName(),
            param: [
                EntryGroupUserFixture::ID => $payload[AddUserToGroupSyncJob::PAYLOAD_KEY_ID],
                EntryGroupUserFixture::ROLE => $payload[AddUserToGroupSyncJob::PAYLOAD_KEY_ROLE],
                EntryGroupUserFixture::USER_ID => $payload[AddUserToGroupSyncJob::PAYLOAD_KEY_USER_ID],
                EntryGroupUserFixture::ENTRY_GROUP_ID => $payload[AddUserToGroupSyncJob::PAYLOAD_KEY_ENTRY_GROUP_ID],
            ],
        );
    }

    /**
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_dispatch_an_event()
    {
        $user = UserFixture::create(persist: true);
        $entryGroup = EntryGroupFixture::create(persist: true);
        $rsaDomainService = RsaDomainService::getInstance();

        $targetUserPublicKey = $rsaDomainService->getUserPublicKey(user: $user);

        $entryGroupAesPasswordEncrypted = $rsaDomainService->encryptByPublic(
            data: EntryGroupUserFixture::DEFAULT_AES_PASSWORD,
            publicKey: $targetUserPublicKey,
        );
        $payload = [
            AddUserToGroupSyncJob::PAYLOAD_KEY_ID => EntryGroupId::generate()->toRaw(),
            AddUserToGroupSyncJob::PAYLOAD_KEY_USER_ID => $user->getId()->toRaw(),
            AddUserToGroupSyncJob::PAYLOAD_KEY_ENTRY_GROUP_ID => $entryGroup->getId()->toRaw(),
            AddUserToGroupSyncJob::PAYLOAD_KEY_ROLE => RoleEnum::MEMBER->value,
            AddUserToGroupSyncJob::PAYLOAD_KEY_ENCRYPTED_AES_PASSWORD => $entryGroupAesPasswordEncrypted,
            AddUserToGroupSyncJob::PAYLOAD_CREATED_AT => $this->faker->dateTime()->format('Y-m-d H:i:s'),
        ];

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryGroupUserCreatedEvent::class],
        );

        new AddUserToGroupSyncJob($payload)->handle();

        $this->assertTrue($testEventHandler->wasDispatched());
    }

}
