<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\Entry\Job;

use App\Module\PasswordBroker\Application\Entry\Event\EntryRenamedEvent;
use App\Module\PasswordBroker\Application\Entry\Job\RenameEntrySyncJob;
use App\Shared\Domain\ValueObject\UpdatedAt;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\PasswordBroker\Fixture\EntryFixture;
use Tests\Shared\IntegrationTestCase;
use Tests\Shared\TestEventHandler;

class RenameEntrySyncJobTest extends IntegrationTestCase
{
    /**
     * @throws PersistenceException
     */
    public function test_it_should_rename_an_entry(): void
    {
        $entry = EntryFixture::create(persist: true);
        $newTitle = $this->faker->name();

        $payload = [
            RenameEntrySyncJob::PAYLOAD_KEY_ID => $entry->id->toRaw(),
            RenameEntrySyncJob::PAYLOAD_KEY_TITLE => $newTitle,
            RenameEntrySyncJob::PAYLOAD_UPDATED_AT => UpdatedAt::now()->toRaw(),
        ];

        new RenameEntrySyncJob($payload)->handle();

        $this->assertDatabaseHas(EntryFixture::getTableName(), [
            EntryFixture::ID => $entry->id->toRaw(),
            EntryFixture::TITLE => $newTitle,
        ]);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_dispatch_an_event(): void
    {
        $entry = EntryFixture::create(persist: true);
        $newTitle = $this->faker->name();

        $payload = [
            RenameEntrySyncJob::PAYLOAD_KEY_ID => $entry->id->toRaw(),
            RenameEntrySyncJob::PAYLOAD_KEY_TITLE => $newTitle,
            RenameEntrySyncJob::PAYLOAD_UPDATED_AT => UpdatedAt::now()->toRaw(),
        ];

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryRenamedEvent::class],
        );

        new RenameEntrySyncJob($payload)->handle();

        $this->assertTrue($testEventHandler->wasDispatched());
    }
}
