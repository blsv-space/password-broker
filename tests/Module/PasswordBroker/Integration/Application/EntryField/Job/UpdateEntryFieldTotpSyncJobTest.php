<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryField\Job;

use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldTotpUpdatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldUpdatedGeneralEvent;
use App\Module\PasswordBroker\Application\EntryField\Job\UpdateEntryFieldTotpSyncJob;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldTotp;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTitle;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFieldFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFixture;
use Tests\Shared\IntegrationTestCase;
use Tests\Shared\TestEventHandler;

class UpdateEntryFieldTotpSyncJobTest extends IntegrationTestCase
{
    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_update_an_entry_field_totp(): void
    {
        $user = UserFixture::create(persist: true);
        $entry = EntryFixture::create(persist: true);
        $entryField = EntryFieldFixture::create(
            attributes: [
                EntryFieldFixture::ENTRY => $entry,
                EntryFieldFixture::TYPE => EntryFieldTypeEnum::TOTP,
                EntryFieldFixture::CREATED_BY => $user->id->toRaw(),
            ],
            persist: true,
        );
        $titleOld = $entryField->title->toRaw();
        $titleUpdated = $this->faker->word();

        $entryField->title = EntryFieldTitle::fromRaw($titleUpdated);

        $this->assertInstanceof(EntryFieldTotp::class, $entryField);

        $payload = $entryField->getAsArray();

        new UpdateEntryFieldTotpSyncJob($payload)->handle();

        $this->assertDatabaseHas(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ID => $entryField->id->toRaw(),
            EntryFieldFixture::ENTRY_ID => $entryField->entryId->toRaw(),
            EntryFieldFixture::TITLE => $entryField->title->toRaw(),
            EntryFieldFixture::TYPE => EntryFieldTypeEnum::TOTP->value,
        ]);

        $this->assertDatabaseMissing(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ID => $entryField->id->toRaw(),
            EntryFieldFixture::ENTRY_ID => $entryField->entryId->toRaw(),
            EntryFieldFixture::TITLE => $titleOld,
            EntryFieldFixture::TYPE => EntryFieldTypeEnum::TOTP->value,
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
                EntryFieldFixture::TYPE => EntryFieldTypeEnum::TOTP,
                EntryFieldFixture::CREATED_BY => $user->id->toRaw(),
            ],
            persist: true,
        );
        $titleUpdated = $this->faker->word();

        $entryField->title = EntryFieldTitle::fromRaw($titleUpdated);

        $this->assertInstanceof(EntryFieldTotp::class, $entryField);

        $payload = $entryField->getAsArray();

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryFieldTotpUpdatedEvent::class, EntryFieldUpdatedGeneralEvent::class],
        );

        new UpdateEntryFieldTotpSyncJob($payload)->handle();

        $this->assertTrue($testEventHandler->wasDispatched());
    }

}
