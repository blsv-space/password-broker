<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryGroupUser\Job;

use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\PasswordBroker\Application\EntryGroupUser\Event\EntryGroupUserAesEncryptedPasswordChangedEvent;
use App\Module\PasswordBroker\Application\EntryGroupUser\Job\ChangeUserEncryptedAesPasswordInGroupSyncJob;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\PasswordBroker\Fixture\EntryGroupUserFixture;
use Tests\Shared\IntegrationTestCase;
use Tests\Shared\TestEventHandler;

class ChangeUserEncryptedAesPasswordInGroupSyncJobTest extends IntegrationTestCase
{
    /**
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_change_the_encrypted_aes_password_of_a_user_in_an_entry_group(): void
    {
        $entryGroupUser = EntryGroupUserFixture::create(persist: true);

        $payload = [
            ChangeUserEncryptedAesPasswordInGroupSyncJob::PAYLOAD_KEY_USER_ID => $entryGroupUser->userId->toRaw(),
            ChangeUserEncryptedAesPasswordInGroupSyncJob::PAYLOAD_KEY_ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
            ChangeUserEncryptedAesPasswordInGroupSyncJob::PAYLOAD_KEY_ENCRYPTED_AES_PASSWORD => 'new_encrypted_aes_password',
            ChangeUserEncryptedAesPasswordInGroupSyncJob::PAYLOAD_UPDATED_AT => $this->faker->dateTime()->format('Y-m-d H:i:s'),
        ];

        new ChangeUserEncryptedAesPasswordInGroupSyncJob($payload)->handle();
        $this->assertDatabaseHas(
            table: EntryGroupUserFixture::getTableName(),
            param: [
                EntryGroupUserFixture::USER_ID => $payload[ChangeUserEncryptedAesPasswordInGroupSyncJob::PAYLOAD_KEY_USER_ID],
                EntryGroupUserFixture::ENCRYPTED_AES_PASSWORD => $payload[ChangeUserEncryptedAesPasswordInGroupSyncJob::PAYLOAD_KEY_ENCRYPTED_AES_PASSWORD],
            ],
        );
    }

    /**
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_dispatch_an_event()
    {
        $entryGroupUser = EntryGroupUserFixture::create(persist: true);

        $payload = [
            ChangeUserEncryptedAesPasswordInGroupSyncJob::PAYLOAD_KEY_USER_ID => $entryGroupUser->userId->toRaw(),
            ChangeUserEncryptedAesPasswordInGroupSyncJob::PAYLOAD_KEY_ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
            ChangeUserEncryptedAesPasswordInGroupSyncJob::PAYLOAD_KEY_ENCRYPTED_AES_PASSWORD => 'new_encrypted_aes_password',
            ChangeUserEncryptedAesPasswordInGroupSyncJob::PAYLOAD_UPDATED_AT => $this->faker->dateTime()->format('Y-m-d H:i:s'),
        ];

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryGroupUserAesEncryptedPasswordChangedEvent::class],
        );

        new ChangeUserEncryptedAesPasswordInGroupSyncJob($payload)->handle();

        $this->assertTrue($testEventHandler->wasDispatched());
    }
}
