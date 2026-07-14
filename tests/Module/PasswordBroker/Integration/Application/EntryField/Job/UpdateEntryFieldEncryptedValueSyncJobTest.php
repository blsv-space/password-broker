<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryField\Job;

use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldEncryptedValueUpdatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldUpdatedGeneralEvent;
use App\Module\PasswordBroker\Application\EntryField\Job\AbstractUpdateEntryFieldSyncJob;
use App\Module\PasswordBroker\Application\EntryField\Job\UpdateEntryFieldEncryptedValueSyncJob;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFieldFixture;
use Tests\Shared\IntegrationTestCase;
use Tests\Shared\TestEventHandler;

class UpdateEntryFieldEncryptedValueSyncJobTest extends IntegrationTestCase
{
    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_update_encrypted_value_of_an_entry_field(): void
    {
        $user = UserFixture::create(persist: true);
        $entryField = EntryFieldFixture::create(
            persist: true,
        );

        $payload = [
            ...$entryField->getAsArray(),
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_KEY_VALUE_ENCRYPTED => $this->faker->password(),
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_KEY_INITIALIZATION_VECTOR => $this->faker->password(12, 12),
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_KEY_TAG => $this->faker->password(16, 16),
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_EXECUTED_BY => $user->id->toRaw(),
        ];
        new UpdateEntryFieldEncryptedValueSyncJob($payload)->handle();

        unset($payload[AbstractUpdateEntryFieldSyncJob::PAYLOAD_EXECUTED_BY]);
        $this->assertDatabaseHas(EntryFieldFixture::getTableName(), $payload);
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
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_KEY_VALUE_ENCRYPTED => $this->faker->password(),
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_KEY_INITIALIZATION_VECTOR => $this->faker->password(12, 12),
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_KEY_TAG => $this->faker->password(16, 16),
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_EXECUTED_BY => $user->id->toRaw(),
        ];

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryFieldEncryptedValueUpdatedEvent::class, EntryFieldUpdatedGeneralEvent::class],
        );

        new UpdateEntryFieldEncryptedValueSyncJob($payload)->handle();

        $this->assertTrue($testEventHandler->wasDispatched());
    }

}
