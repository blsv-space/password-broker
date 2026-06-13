<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryField\Job;

use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldLinkUpdatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldUpdatedGeneralEvent;
use App\Module\PasswordBroker\Application\EntryField\Job\AbstractUpdateEntryFieldSyncJob;
use App\Module\PasswordBroker\Application\EntryField\Job\UpdateEntryFieldLinkSyncJob;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldLink;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTitle;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFieldFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFixture;
use Tests\Shared\IntegrationTestCase;
use Tests\Shared\TestEventHandler;

class UpdateEntryFieldLinkSyncJobTest extends IntegrationTestCase
{
    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_update_an_entry_field_link(): void
    {
        $user = UserFixture::create(persist: true);
        $entry = EntryFixture::create(persist: true);
        $entryField = EntryFieldFixture::create(
            attributes: [
                EntryFieldFixture::ENTRY => $entry,
                EntryFieldFixture::TYPE => EntryFieldTypeEnum::LINK,
                EntryFieldFixture::CREATED_BY => $user->id->toRaw(),
            ],
            persist: true,
        );
        $titleOld = $entryField->title->toRaw();
        $titleUpdated = $this->faker->word();

        $entryField->title = EntryFieldTitle::fromRaw($titleUpdated);

        $this->assertInstanceof(EntryFieldLink::class, $entryField);

        $payload = [
            ...$entryField->getAsArray(),
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_EXECUTED_BY => $user->id->toRaw(),
        ];

        new UpdateEntryFieldLinkSyncJob($payload)->handle();

        $this->assertDatabaseHas(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ID => $entryField->id->toRaw(),
            EntryFieldFixture::ENTRY_ID => $entryField->entryId->toRaw(),
            EntryFieldFixture::TITLE => $entryField->title->toRaw(),
            EntryFieldFixture::TYPE => EntryFieldTypeEnum::LINK->value,
        ]);

        $this->assertDatabaseMissing(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ID => $entryField->id->toRaw(),
            EntryFieldFixture::ENTRY_ID => $entryField->entryId->toRaw(),
            EntryFieldFixture::TITLE => $titleOld,
            EntryFieldFixture::TYPE => EntryFieldTypeEnum::LINK->value,
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
                EntryFieldFixture::TYPE => EntryFieldTypeEnum::LINK,
                EntryFieldFixture::CREATED_BY => $user->id->toRaw(),
            ],
            persist: true,
        );
        $titleUpdated = $this->faker->word();

        $entryField->title = EntryFieldTitle::fromRaw($titleUpdated);

        $this->assertInstanceof(EntryFieldLink::class, $entryField);

        $payload = [
            ...$entryField->getAsArray(),
            AbstractUpdateEntryFieldSyncJob::PAYLOAD_EXECUTED_BY => $user->id->toRaw(),
        ];

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryFieldLinkUpdatedEvent::class, EntryFieldUpdatedGeneralEvent::class],
        );

        new UpdateEntryFieldLinkSyncJob($payload)->handle();

        $this->assertTrue($testEventHandler->wasDispatched());
    }

}
