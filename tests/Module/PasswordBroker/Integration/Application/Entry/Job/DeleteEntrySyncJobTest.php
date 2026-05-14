<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\Entry\Job;

use App\Module\PasswordBroker\Application\Entry\Event\EntryDeletedEvent;
use App\Module\PasswordBroker\Application\Entry\Job\DeleteEntrySyncJob;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\PasswordBroker\Fixture\EntryFixture;
use Tests\Shared\IntegrationTestCase;
use Tests\Shared\TestEventHandler;

class DeleteEntrySyncJobTest extends IntegrationTestCase
{
    /**
     * @throws PersistenceException
     */
    public function test_it_should_delete_an_entry(): void
    {
        $entry = EntryFixture::create(persist: true);

        $this->assertDatabaseHas(
            table: EntryFixture::getTableName(),
            param: [
                EntryFixture::ID => $entry->id->toRaw(),
                EntryFixture::DELETED_AT => null,
            ],
        );

        $payload = [
            DeleteEntrySyncJob::PAYLOAD_KEY_ID => $entry->id->toRaw(),
        ];

        new DeleteEntrySyncJob($payload)->handle();

        $this->assertDatabaseMissing(
            table: EntryFixture::getTableName(),
            param: [
                EntryFixture::ID => $entry->id->toRaw(),
                EntryFixture::DELETED_AT => null,
            ],
        );
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_dispatch_an_event(): void
    {
        $entry = EntryFixture::create(persist: true);

        $this->assertDatabaseHas(
            table: EntryFixture::getTableName(),
            param: [
                EntryFixture::ID => $entry->id->toRaw(),
                EntryFixture::DELETED_AT => null,
            ],
        );

        $payload = [
            DeleteEntrySyncJob::PAYLOAD_KEY_ID => $entry->id->toRaw(),
        ];

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryDeletedEvent::class],
        );

        new DeleteEntrySyncJob($payload)->handle();

        $this->assertTrue($testEventHandler->wasDispatched());
    }
}
