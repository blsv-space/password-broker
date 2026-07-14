<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryFieldHistory\Job;

use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\PasswordBroker\Application\EntryField\Job\AbstractUpdateEntryFieldSyncJob;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Event\EntryFieldHistoryEncryptedValueUpdatedEvent;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Event\EntryFieldHistoryUpdatedGeneralEvent;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Job\UpdateEntryFieldHistoryEncryptedValueSyncJob;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFieldFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFieldHistoryFixture;
use Tests\Shared\IntegrationTestCase;
use Tests\Shared\TestEventHandler;

class UpdateEntryFieldHistoryEncryptedValueSyncJobTest extends IntegrationTestCase
{
    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_update_encrypted_value_of_an_entry_field_history(): void
    {
        $user = UserFixture::create(persist: true);
        $entryField = EntryFieldFixture::create(
            persist: true,
        );
        $entryFieldHistory = EntryFieldHistoryFixture::create(
            attributes: [
                EntryFieldHistoryFixture::ENTRY_FIELD => $entryField,
            ],
            persist: true,
        );

        $payload = [
            ...$entryFieldHistory->getAsArray(),
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_KEY_VALUE_ENCRYPTED => $this->faker->password(),
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_KEY_INITIALIZATION_VECTOR => $this->faker->password(12, 12),
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_KEY_TAG => $this->faker->password(16, 16),
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_EXECUTED_BY => $user->id->toRaw(),
        ];

        new UpdateEntryFieldHistoryEncryptedValueSyncJob($payload)->handle();

        unset($payload[AbstractUpdateEntryFieldSyncJob::PAYLOAD_EXECUTED_BY]);
        $this->assertDatabaseHas(EntryFieldHistoryFixture::getTableName(), $payload);
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
        $entryFieldHistory = EntryFieldHistoryFixture::create(
            attributes: [
                EntryFieldHistoryFixture::ENTRY_FIELD => $entryField,
            ],
            persist: true,
        );

        $payload = [
            ...$entryFieldHistory->getAsArray(),
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_KEY_VALUE_ENCRYPTED => $this->faker->password(),
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_KEY_INITIALIZATION_VECTOR => $this->faker->password(12, 12),
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_KEY_TAG => $this->faker->password(16, 16),
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_EXECUTED_BY => $user->id->toRaw(),
        ];

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryFieldHistoryEncryptedValueUpdatedEvent::class, EntryFieldHistoryUpdatedGeneralEvent::class],
        );

        new UpdateEntryFieldHistoryEncryptedValueSyncJob($payload)->handle();

        $this->assertTrue($testEventHandler->wasDispatched());
    }

}
