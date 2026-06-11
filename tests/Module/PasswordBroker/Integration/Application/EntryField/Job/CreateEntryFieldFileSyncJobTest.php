<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryField\Job;

use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldCreatedGeneralEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldFileCreatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Job\CreateEntryFieldFileSyncJob;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldFile;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFieldFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFixture;
use Tests\Shared\IntegrationTestCase;
use Tests\Shared\TestEventHandler;

class CreateEntryFieldFileSyncJobTest extends IntegrationTestCase
{
    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_create_an_entry_field_file(): void
    {
        $user = UserFixture::create(persist: true);
        $entry = EntryFixture::create(persist: true);
        $entryField = EntryFieldFixture::create(
            attributes: [
                EntryFieldFixture::ENTRY => $entry,
                EntryFieldFixture::TYPE => EntryFieldTypeEnum::FILE,
                EntryFieldFixture::CREATED_BY => $user->id->toRaw(),
            ],
        );

        $this->assertInstanceof(EntryFieldFile::class, $entryField);

        $payload = $entryField->getAsArray();

        new CreateEntryFieldFileSyncJob($payload)->handle();

        $this->assertDatabaseHas(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ID => $entryField->id->toRaw(),
            EntryFieldFixture::ENTRY_ID => $entryField->entryId->toRaw(),
            EntryFieldFixture::TITLE => $entryField->title->toRaw(),
            EntryFieldFixture::TYPE => EntryFieldTypeEnum::FILE->value,
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
                EntryFieldFixture::TYPE => EntryFieldTypeEnum::FILE,
                EntryFieldFixture::CREATED_BY => $user->id->toRaw(),
            ],
        );

        $this->assertInstanceof(EntryFieldFile::class, $entryField);

        $payload = $entryField->getAsArray();
        $testEventHandler = new TestEventHandler(
            eventNames: [EntryFieldFileCreatedEvent::class, EntryFieldCreatedGeneralEvent::class],
        );

        new CreateEntryFieldFileSyncJob($payload)->handle();


        $this->assertTrue($testEventHandler->wasDispatched());
    }

}
