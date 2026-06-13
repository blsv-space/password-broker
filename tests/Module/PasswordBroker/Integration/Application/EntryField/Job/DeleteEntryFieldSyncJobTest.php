<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryField\Job;

use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldDeletedEvent;
use App\Module\PasswordBroker\Application\EntryField\Job\DeleteEntryFieldSyncJob;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFieldFixture;
use Tests\Shared\IntegrationTestCase;
use Tests\Shared\TestEventHandler;

class DeleteEntryFieldSyncJobTest extends IntegrationTestCase
{
    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_delete_an_entry_field(): void
    {
        $user = UserFixture::create(persist: true);
        $entryField = EntryFieldFixture::create(
            attributes: [
                EntryFieldFixture::CREATED_BY => $user->id->toRaw(),
            ],
            persist: true,
        );

        $payload = [
            ...$entryField->getAsArray(),
            DeleteEntryFieldSyncJob::PAYLOAD_EXECUTED_BY => $user->id->toRaw(),
        ];

        $entryFieldDeleted = new DeleteEntryFieldSyncJob($payload)->handle();

        $this->assertDatabaseHas(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ID => $entryField->id->toRaw(),
            EntryFieldFixture::ENTRY_ID => $entryField->entryId->toRaw(),
            EntryFieldFixture::DELETED_AT => $entryFieldDeleted->deletedAt->toRaw(),
        ]);

        $this->assertDatabaseMissing(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ID => $entryField->id->toRaw(),
            EntryFieldFixture::ENTRY_ID => $entryField->entryId->toRaw(),
            EntryFieldFixture::DELETED_AT => null,
        ]);
    }

    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_dispatch_an_event(): void
    {
        $user = UserFixture::create(persist: true);
        $entryField = EntryFieldFixture::create(
            persist: true,
        );

        $payload = [
            ...$entryField->getAsArray(),
            DeleteEntryFieldSyncJob::PAYLOAD_EXECUTED_BY => $user->id->toRaw(),
        ];

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryFieldDeletedEvent::class],
        );

        new DeleteEntryFieldSyncJob($payload)->handle();

        $this->assertTrue($testEventHandler->wasDispatched());
    }

}
