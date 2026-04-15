<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryGroup\Job;

use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\PasswordBroker\Application\EntryGroup\Event\EntryGroupCreatedEvent;
use App\Module\PasswordBroker\Application\EntryGroup\Job\CreateEntryGroupSyncJob;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\TargetGroupNotFoundException;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\TargetUserNotFoundException;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Random\RandomException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupFixture;
use Tests\Shared\IntegrationTestCase;
use Tests\Shared\TestEventHandler;

class CreateEntryGroupSyncJobTest extends IntegrationTestCase
{
    /**
     * @return void
     * @throws PersistenceException
     * @throws RandomException
     * @throws RsaDomainServiceException
     * @throws TargetGroupNotFoundException
     * @throws TargetUserNotFoundException
     */
    public function test_it_should_create_an_entry_group(): void
    {
        $user = UserFixture::create(persist: true);
        $payload = [
            CreateEntryGroupSyncJob::PAYLOAD_KEY_ID => EntryGroupId::generate()->toRaw(),
            CreateEntryGroupSyncJob::PAYLOAD_KEY_NAME => $this->faker->name(),
            CreateEntryGroupSyncJob::PAYLOAD_KEY_USER_ID => $user->getId()->toRaw(),
        ];

        new CreateEntryGroupSyncJob($payload)->handle();
        $this->assertDatabaseHas(
            EntryGroupFixture::getTableName(),
            [EntryGroupFixture::ID => $payload[CreateEntryGroupSyncJob::PAYLOAD_KEY_ID]],
        );
    }

    /**
     * @throws PersistenceException
     * @throws RandomException
     * @throws RsaDomainServiceException
     * @throws TargetGroupNotFoundException
     * @throws TargetUserNotFoundException
     */
    public function test_it_should_dispatch_an_event(): void
    {
        $user = UserFixture::create(persist: true);

        $payload = [
            CreateEntryGroupSyncJob::PAYLOAD_KEY_ID => EntryGroupId::generate()->toRaw(),
            CreateEntryGroupSyncJob::PAYLOAD_KEY_NAME => $this->faker->name(),
            CreateEntryGroupSyncJob::PAYLOAD_KEY_USER_ID => $user->getId()->toRaw(),
        ];

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryGroupCreatedEvent::class],
        );

        new CreateEntryGroupSyncJob($payload)->handle();

        $this->assertTrue($testEventHandler->wasDispatched());
    }

    /**
     * @return void
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     * @throws TargetGroupNotFoundException
     * @throws TargetUserNotFoundException
     * @throws RandomException
     */
    public function test_it_should_create_a_child_entry_group(): void
    {
        $user = UserFixture::create(persist: true);

        $childEntryGroupId = EntryGroupId::generate()->toRaw();
        $entryGroupParent = EntryGroupFixture::create(persist: true);

        $payload = [
            CreateEntryGroupSyncJob::PAYLOAD_KEY_ID => $childEntryGroupId,
            CreateEntryGroupSyncJob::PAYLOAD_KEY_NAME => $this->faker->name(),
            CreateEntryGroupSyncJob::PAYLOAD_KEY_PARENT_ENTRY_GROUP_ID => $entryGroupParent->getId()->toRaw(),
            CreateEntryGroupSyncJob::PAYLOAD_KEY_USER_ID => $user->getId()->toRaw(),
        ];

        new CreateEntryGroupSyncJob($payload)->handle();
        $this->assertDatabaseHas(
            EntryGroupFixture::getTableName(),
            [
                EntryGroupFixture::ID => $childEntryGroupId,
                EntryGroupFixture::PARENT_ENTRY_GROUP_ID => $entryGroupParent->getId()->toRaw(),
            ],
        );
    }
}
