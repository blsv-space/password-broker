<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryFieldHistory\EventHandler;

use App\Module\Identity\Application\User\Service\Exception\AuthException;
use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\PasswordBroker\Application\EntryField\Service\EntryFieldApplicationService;
use App\Module\PasswordBroker\Application\EntryField\Service\Exception\EntryFieldNotFountException;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\AuthUserNotInEntryGroupException;
use App\Module\PasswordBroker\Domain\Entry\Entity\Entry;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldPassword;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Enum\RoleEnum;
use App\Shared\Domain\Security\Encryption\Exception\EncryptionException;
use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;
use App\Shared\Infrastructure\Security\Exception\JwtTokenExpiredException;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use ReflectionException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFieldFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFieldHistoryFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupUserFixture;
use Tests\Shared\IntegrationTestCase;

class AbstractEntryFieldUpdatedEventHandlerTest extends IntegrationTestCase
{
    private User $authUser;

    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     */
    #[\Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->authUser = UserFixture::create(persist: true);
        $this->actAs($this->authUser);
    }

    /**
     * @throws AuthUserNotInEntryGroupException
     * @throws EncryptionException
     * @throws JwtTokenExpiredException
     * @throws JwtInvalidTokenException
     * @throws AuthException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     * @throws EntryFieldNotFountException
     */
    public function test_it_should_create_entry_field_history_on_entry_field_update(): void
    {
        $entry = $this->createAnEntry();
        $entryFieldApplicationService = EntryFieldApplicationService::getInstance();

        $entryField = EntryFieldFixture::create(
            attributes: [
                EntryFieldFixture::ENTRY => $entry,
                EntryFieldFixture::TYPE => EntryFieldTypeEnum::PASSWORD,
            ],
            persist: true,
        );
        $this->assertInstanceOf(EntryFieldPassword::class, $entryField);
        $newTitle = $this->faker->word;
        $entryFieldApplicationService->updateEntryFieldSync(
            id: $entryField->id->toRaw(),
            title: $newTitle,
        );

        $this->assertDatabaseHas(
            table: EntryFieldFixture::getTableName(),
            param: [
                EntryFieldFixture::ID => $entryField->id->toRaw(),
            ],
        );

        $this->assertDatabaseHas(
            table: EntryFieldHistoryFixture::getTableName(),
            param: [
                EntryFieldHistoryFixture::ENTRY_FIELD_ID => $entryField->id->toRaw(),
                EntryFieldHistoryFixture::TITLE => $newTitle,
            ],
        );
    }

    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    private function createAnEntry(): Entry
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $this->authUser->getId()->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->getId()->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::MEMBER->value,
            ],
            persist: true,
        );
        return EntryFixture::create(attributes: [EntryFixture::ENTRY_GROUP => $entryGroup], persist: true);
    }
}
