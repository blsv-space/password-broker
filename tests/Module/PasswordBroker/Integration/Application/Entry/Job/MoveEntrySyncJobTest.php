<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\Entry\Job;

use App\Module\PasswordBroker\Application\Entry\Event\EntryMovedEvent;
use App\Module\PasswordBroker\Application\Entry\Job\MoveEntrySyncJob;
use App\Shared\Domain\ValueObject\UpdatedAt;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\PasswordBroker\Fixture\EntryFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupUserFixture;
use Tests\Shared\IntegrationTestCase;
use Tests\Shared\TestEventHandler;

class MoveEntrySyncJobTest extends IntegrationTestCase
{
    /**
     * @throws PersistenceException
     */
    public function test_it_should_move_an_entry(): void
    {
        $entryGroupTarget = EntryGroupFixture::create(persist: true);
        $entry = EntryFixture::create(persist: true);

        $payload = [
            MoveEntrySyncJob::PAYLOAD_KEY_ID => $entry->id->toRaw(),
            MoveEntrySyncJob::PAYLOAD_KEY_ENTRY_GROUP_TARGET_ID => $entryGroupTarget->id->toRaw(),
            MoveEntrySyncJob::PAYLOAD_KEY_ENTRY_GROUP_ORIGIN_AES_PASSWORD => EntryGroupUserFixture::DEFAULT_AES_PASSWORD,
            MoveEntrySyncJob::PAYLOAD_KEY_ENTRY_GROUP_TARGET_AES_PASSWORD => EntryGroupUserFixture::DEFAULT_AES_PASSWORD,
            MoveEntrySyncJob::PAYLOAD_UPDATED_AT => UpdatedAt::now()->toRaw(),
        ];

        new MoveEntrySyncJob($payload)->handle();

        $this->assertDatabaseHas(
            table: EntryFixture::getTableName(),
            param: [
                EntryFixture::ID => $entry->id->toRaw(),
                EntryFixture::ENTRY_GROUP_ID => $entryGroupTarget->id->toRaw(),
            ],
        );
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_dispatch_an_event(): void
    {
        $entryGroupTarget = EntryGroupFixture::create(persist: true);
        $entry = EntryFixture::create(persist: true);

        $payload = [
            MoveEntrySyncJob::PAYLOAD_KEY_ID => $entry->id->toRaw(),
            MoveEntrySyncJob::PAYLOAD_KEY_ENTRY_GROUP_TARGET_ID => $entryGroupTarget->id->toRaw(),
            MoveEntrySyncJob::PAYLOAD_KEY_ENTRY_GROUP_ORIGIN_AES_PASSWORD => EntryGroupUserFixture::DEFAULT_AES_PASSWORD,
            MoveEntrySyncJob::PAYLOAD_KEY_ENTRY_GROUP_TARGET_AES_PASSWORD => EntryGroupUserFixture::DEFAULT_AES_PASSWORD,
            MoveEntrySyncJob::PAYLOAD_UPDATED_AT => UpdatedAt::now()->toRaw(),
        ];

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryMovedEvent::class],
        );
        new MoveEntrySyncJob($payload)->handle();

        $this->assertTrue($testEventHandler->wasDispatched());
    }
}
