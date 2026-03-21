<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryGroup\Job;

use App\Module\PasswordBroker\Application\EntryGroup\Event\EntryGroupCreatedEvent;
use App\Module\PasswordBroker\Application\EntryGroup\Job\CreateEntryGroupSyncJob;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\PasswordBroker\Fixture\EntryGroupFixture;
use Tests\Shared\IntegrationTestCase;
use Tests\Shared\TestEventHandler;

class CreateEntryGroupSyncJobTest extends IntegrationTestCase
{
    /**
     * @throws PersistenceException
     */
    public function test_it_should_create_an_entry_group(): void
    {
        $payload = [
            CreateEntryGroupSyncJob::PAYLOAD_KEY_ID => EntryGroupId::generate()->toRaw(),
            CreateEntryGroupSyncJob::PAYLOAD_KEY_NAME => $this->faker->name(),
        ];

        new CreateEntryGroupSyncJob($payload)->handle();
        $this->assertDatabaseHas(
            EntryGroupFixture::getTableName(),
            [EntryGroupFixture::ID => $payload[CreateEntryGroupSyncJob::PAYLOAD_KEY_ID]],
        );
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_dispatch_an_event(): void
    {
        $payload = [
            CreateEntryGroupSyncJob::PAYLOAD_KEY_ID => EntryGroupId::generate()->toRaw(),
            CreateEntryGroupSyncJob::PAYLOAD_KEY_NAME => $this->faker->name(),
        ];

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryGroupCreatedEvent::class],
        );

        new CreateEntryGroupSyncJob($payload)->handle();

        $this->assertTrue($testEventHandler->wasDispatched());
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_create_a_child_entry_group(): void
    {
        $childEntryGroupId = EntryGroupId::generate()->toRaw();
        $entryGroupParent = EntryGroupFixture::create(persist: true);

        $payload = [
            CreateEntryGroupSyncJob::PAYLOAD_KEY_ID => $childEntryGroupId,
            CreateEntryGroupSyncJob::PAYLOAD_KEY_NAME => $this->faker->name(),
            CreateEntryGroupSyncJob::PAYLOAD_KEY_PARENT_ENTRY_GROUP_ID => $entryGroupParent->getId()->toRaw(),
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
