<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\Entry\Job;

use App\Module\PasswordBroker\Application\Entry\Event\EntryCreatedEvent;
use App\Module\PasswordBroker\Application\Entry\Job\CreateEntrySyncJob;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\PasswordBroker\Fixture\EntryFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupFixture;
use Tests\Shared\IntegrationTestCase;
use Tests\Shared\TestEventHandler;

class CreateEntrySyncJobTest extends IntegrationTestCase
{
    /**
     * @throws PersistenceException
     */
    public function test_it_should_create_an_entry(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        $entry = EntryFixture::create(
            attributes: [
                EntryFixture::ENTRY_GROUP => $entryGroup,
            ],
        );

        $payload = [
            CreateEntrySyncJob::PAYLOAD_KEY_ID => $entry->id->toRaw(),
            CreateEntrySyncJob::PAYLOAD_KEY_ENTRY_GROUP_ID => $entry->entryGroupId->toRaw(),
            CreateEntrySyncJob::PAYLOAD_KEY_TITLE => $entry->title->toRaw(),
            CreateEntrySyncJob::PAYLOAD_CREATED_AT => $entry->createdAt->toRaw(),
        ];

        new CreateEntrySyncJob($payload)->handle();

        $this->assertDatabaseHas(EntryFixture::getTableName(), [
            EntryFixture::ID => $entry->id->toRaw(),
            EntryFixture::ENTRY_GROUP_ID => $entry->entryGroupId->toRaw(),
            EntryFixture::TITLE => $entry->title->toRaw(),
        ]);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_dispatch_an_event(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        $entry = EntryFixture::create(
            attributes: [
                EntryFixture::ENTRY_GROUP => $entryGroup,
            ],
        );

        $payload = [
            CreateEntrySyncJob::PAYLOAD_KEY_ID => $entry->id->toRaw(),
            CreateEntrySyncJob::PAYLOAD_KEY_ENTRY_GROUP_ID => $entry->entryGroupId->toRaw(),
            CreateEntrySyncJob::PAYLOAD_KEY_TITLE => $entry->title->toRaw(),
            CreateEntrySyncJob::PAYLOAD_CREATED_AT => $entry->createdAt->toRaw(),
        ];

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryCreatedEvent::class],
        );

        new CreateEntrySyncJob($payload)->handle();

        $this->assertTrue($testEventHandler->wasDispatched());
    }

}
