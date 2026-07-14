<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryGroupUser\Job;

use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\PasswordBroker\Application\EntryGroupUser\Event\EntryGroupUserDeletedEvent;
use App\Module\PasswordBroker\Application\EntryGroupUser\Job\DeleteUserFromGroupSyncJob;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\PasswordBroker\Fixture\EntryGroupUserFixture;
use Tests\Shared\IntegrationTestCase;
use Tests\Shared\TestEventHandler;

class DeleteUserFromGroupSyncJobTest extends IntegrationTestCase
{
    /**
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_delete_a_user_from_an_entry_group(): void
    {
        $entryGroupUser = EntryGroupUserFixture::create(
            persist: true,
        );

        $secondUserInGroup = EntryGroupUserFixture::create(
            attributes: [EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw()],
            persist: true,
        );

        $payload = [
            DeleteUserFromGroupSyncJob::PAYLOAD_KEY_ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
            DeleteUserFromGroupSyncJob::PAYLOAD_KEY_USER_ID => $entryGroupUser->userId->toRaw(),
        ];

        new DeleteUserFromGroupSyncJob($payload)->handle();
        $this->assertDatabaseMissing(
            table: EntryGroupUserFixture::getTableName(),
            param: [
                EntryGroupUserFixture::ID => $entryGroupUser->getId()->toRaw(),
            ],
        );

        $this->assertDatabaseHas(
            table: EntryGroupUserFixture::getTableName(),
            param: [
                EntryGroupUserFixture::ID => $secondUserInGroup->getId()->toRaw(),
            ],
        );
    }

    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_dispatch_an_event(): void
    {
        $entryGroupUser = EntryGroupUserFixture::create(
            persist: true,
        );

        $payload = [
            DeleteUserFromGroupSyncJob::PAYLOAD_KEY_ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
            DeleteUserFromGroupSyncJob::PAYLOAD_KEY_USER_ID => $entryGroupUser->userId->toRaw(),
        ];

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryGroupUserDeletedEvent::class],
        );

        new DeleteUserFromGroupSyncJob($payload)->handle();

        $this->assertTrue($testEventHandler->wasDispatched());
    }
}
