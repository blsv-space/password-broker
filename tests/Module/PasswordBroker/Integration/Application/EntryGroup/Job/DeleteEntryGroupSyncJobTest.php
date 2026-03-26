<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryGroup\Job;

use App\Module\PasswordBroker\Application\EntryGroup\Job\DeleteEntryGroupSyncJob;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\PasswordBroker\Fixture\EntryGroupFixture;
use Tests\Shared\IntegrationTestCase;

class DeleteEntryGroupSyncJobTest extends IntegrationTestCase
{
    /**
     * @throws PersistenceException
     */
    public function test_it_should_delete_an_entry_group(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);

        $this->assertDatabaseHas(EntryGroupFixture::getTableName(), [EntryGroupFixture::ID => $entryGroup->id->toRaw()]);

        $payload = [
            DeleteEntryGroupSyncJob::PAYLOAD_KEY_ID => $entryGroup->id->toRaw(),
        ];

        new DeleteEntryGroupSyncJob($payload)->handle();

        $this->assertDatabaseMissing(
            table: EntryGroupFixture::getTableName(),
            param: [
                EntryGroupFixture::ID => $entryGroup->id->toRaw(),
                EntryGroupFixture::DELETED_AT => null,
            ],
        );
    }
}
