<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryGroup\Job;

use App\Module\PasswordBroker\Application\EntryGroup\Event\EntryGroupRenamedEvent;
use App\Module\PasswordBroker\Application\EntryGroup\Job\RenameEntryGroupSyncJob;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\PasswordBroker\Fixture\EntryGroupFixture;
use Tests\Shared\IntegrationTestCase;
use Tests\Shared\TestEventHandler;

class RenameEntryGroupSyncJobTest extends IntegrationTestCase
{
    /**
     * @throws PersistenceException
     */
    public function test_it_should_rename_an_entry_group(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);

        $oldName = $entryGroup->name->toRaw();
        $newName = $oldName . ' ' . $this->faker->word();

        $payload = [
            RenameEntryGroupSyncJob::PAYLOAD_KEY_ID => $entryGroup->id->toRaw(),
            RenameEntryGroupSyncJob::PAYLOAD_KEY_NAME => $newName,
            RenameEntryGroupSyncJob::PAYLOAD_UPDATED_AT => $this->faker->dateTime()->format('Y-m-d H:i:s'),
        ];

        new RenameEntryGroupSyncJob($payload)->handle();

        $this->assertDatabaseHas(EntryGroupFixture::getTableName(), [
            EntryGroupFixture::ID => $entryGroup->id->toRaw(),
            EntryGroupFixture::NAME => $newName,
        ]);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_dispatch_an_event(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);

        $oldName = $entryGroup->name->toRaw();
        $newName = $oldName . ' ' . $this->faker->word();

        $payload = [
            RenameEntryGroupSyncJob::PAYLOAD_KEY_ID => $entryGroup->id->toRaw(),
            RenameEntryGroupSyncJob::PAYLOAD_KEY_NAME => $newName,
            RenameEntryGroupSyncJob::PAYLOAD_UPDATED_AT => $this->faker->dateTime()->format('Y-m-d H:i:s'),
        ];

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryGroupRenamedEvent::class],
        );

        new RenameEntryGroupSyncJob($payload)->handle();

        $this->assertTrue($testEventHandler->wasDispatched());
    }
}
