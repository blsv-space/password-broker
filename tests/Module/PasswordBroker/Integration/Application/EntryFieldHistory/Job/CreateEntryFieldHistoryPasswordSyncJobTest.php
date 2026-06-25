<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryFieldHistory\Job;

use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\PasswordBroker\Application\EntryField\Job\AbstractCreateEntryFieldSyncJob;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Event\EntryFieldHistoryCreatedGeneralEvent;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Event\EntryFieldHistoryPasswordCreatedEvent;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Job\CreateEntryFieldHistoryPasswordSyncJob;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\EntryFieldHistoryPassword;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFieldFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFieldHistoryFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFixture;
use Tests\Shared\IntegrationTestCase;
use Tests\Shared\TestEventHandler;

class CreateEntryFieldHistoryPasswordSyncJobTest extends IntegrationTestCase
{
    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_create_an_entry_field_history_password(): void
    {
        $user = UserFixture::create(persist: true);
        $entry = EntryFixture::create(persist: true);
        $entryField = EntryFieldFixture::create(
            attributes: [
                EntryFieldFixture::ENTRY => $entry,
                EntryFieldFixture::TYPE => EntryFieldTypeEnum::PASSWORD,
                EntryFieldFixture::CREATED_BY => $user->id->toRaw(),
            ],
            persist: true,
        );
        $entryFieldHistory = EntryFieldHistoryFixture::create(
            attributes: [
                EntryFieldHistoryFixture::ENTRY_FIELD => $entryField,
                EntryFieldHistoryFixture::CREATED_BY => $user->id->toRaw(),
            ],
        );

        $this->assertInstanceof(EntryFieldHistoryPassword::class, $entryFieldHistory);

        $payload = [
            ...$entryFieldHistory->getAsArray(),
            AbstractCreateEntryFieldSyncJob::PAYLOAD_EXECUTED_BY => $user->id->toRaw(),
        ];

        new CreateEntryFieldHistoryPasswordSyncJob($payload)->handle();

        $this->assertDatabaseHas(EntryFieldHistoryFixture::getTableName(), [
            EntryFieldHistoryFixture::ID => $entryFieldHistory->id->toRaw(),
            EntryFieldHistoryFixture::ENTRY_FIELD_ID => $entryFieldHistory->entryFieldId->toRaw(),
            EntryFieldHistoryFixture::TITLE => $entryFieldHistory->title->toRaw(),
            EntryFieldHistoryFixture::TYPE => EntryFieldTypeEnum::PASSWORD->value,
        ]);
    }

    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_dispatch_an_event(): void
    {
        $user = UserFixture::create(persist: true);
        $entry = EntryFixture::create(persist: true);
        $entryField = EntryFieldFixture::create(
            attributes: [
                EntryFieldFixture::ENTRY => $entry,
                EntryFieldFixture::TYPE => EntryFieldTypeEnum::PASSWORD,
                EntryFieldFixture::CREATED_BY => $user->id->toRaw(),
            ],
            persist: true,
        );
        $entryFieldHistory = EntryFieldHistoryFixture::create(
            attributes: [
                EntryFieldHistoryFixture::ENTRY_FIELD => $entryField,
                EntryFieldHistoryFixture::CREATED_BY => $user->id->toRaw(),
            ],
        );

        $this->assertInstanceof(EntryFieldHistoryPassword::class, $entryFieldHistory);

        $payload = [
            ...$entryFieldHistory->getAsArray(),
            AbstractCreateEntryFieldSyncJob::PAYLOAD_EXECUTED_BY => $user->id->toRaw(),
        ];
        $testEventHandler = new TestEventHandler(
            eventNames: [EntryFieldHistoryPasswordCreatedEvent::class, EntryFieldHistoryCreatedGeneralEvent::class],
        );

        new CreateEntryFieldHistoryPasswordSyncJob($payload)->handle();


        $this->assertTrue($testEventHandler->wasDispatched());
    }

}
