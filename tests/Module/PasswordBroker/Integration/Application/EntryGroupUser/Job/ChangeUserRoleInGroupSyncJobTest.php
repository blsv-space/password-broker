<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryGroupUser\Job;

use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\PasswordBroker\Application\EntryGroupUser\Event\EntryGroupUserRoleChangedEvent;
use App\Module\PasswordBroker\Application\EntryGroupUser\Job\ChangeUserRoleInGroupSyncJob;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Enum\RoleEnum;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\PasswordBroker\Fixture\EntryGroupUserFixture;
use Tests\Shared\IntegrationTestCase;
use Tests\Shared\TestEventHandler;

class ChangeUserRoleInGroupSyncJobTest extends IntegrationTestCase
{
    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_change_a_user_role_in_an_entry_group(): void
    {
        $entryGroupUser = EntryGroupUserFixture::create(
            attributes: [EntryGroupUserFixture::ROLE => RoleEnum::MEMBER->value],
            persist: true,
        );

        $payload = [
            ChangeUserRoleInGroupSyncJob::PAYLOAD_KEY_USER_ID => $entryGroupUser->userId->toRaw(),
            ChangeUserRoleInGroupSyncJob::PAYLOAD_KEY_ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
            ChangeUserRoleInGroupSyncJob::PAYLOAD_KEY_ROLE => RoleEnum::MODERATOR->value,
            ChangeUserRoleInGroupSyncJob::PAYLOAD_UPDATED_AT => $this->faker->dateTime()->format('Y-m-d H:i:s'),
        ];

        new ChangeUserRoleInGroupSyncJob($payload)->handle();
        $this->assertDatabaseHas(
            table: EntryGroupUserFixture::getTableName(),
            param: [
                EntryGroupUserFixture::USER_ID => $payload[ChangeUserRoleInGroupSyncJob::PAYLOAD_KEY_USER_ID],
                EntryGroupUserFixture::ENTRY_GROUP_ID => $payload[ChangeUserRoleInGroupSyncJob::PAYLOAD_KEY_ENTRY_GROUP_ID],
                EntryGroupUserFixture::ROLE => $payload[ChangeUserRoleInGroupSyncJob::PAYLOAD_KEY_ROLE],
            ],
        );
    }

    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_dispatch_an_event()
    {
        $entryGroupUser = EntryGroupUserFixture::create(
            attributes: [EntryGroupUserFixture::ROLE => RoleEnum::MEMBER->value],
            persist: true,
        );

        $payload = [
            ChangeUserRoleInGroupSyncJob::PAYLOAD_KEY_USER_ID => $entryGroupUser->userId->toRaw(),
            ChangeUserRoleInGroupSyncJob::PAYLOAD_KEY_ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
            ChangeUserRoleInGroupSyncJob::PAYLOAD_KEY_ROLE => RoleEnum::MODERATOR->value,
            ChangeUserRoleInGroupSyncJob::PAYLOAD_UPDATED_AT => $this->faker->dateTime()->format('Y-m-d H:i:s'),
        ];

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryGroupUserRoleChangedEvent::class],
        );

        new ChangeUserRoleInGroupSyncJob($payload)->handle();

        $this->assertTrue($testEventHandler->wasDispatched());
    }
}
