<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryField\Provider;

use App\Module\PasswordBroker\Application\EntryField\DTO\EntryFieldResponse\EntryFieldFileResponse;
use App\Module\PasswordBroker\Application\EntryField\DTO\EntryFieldResponse\EntryFieldLinkResponse;
use App\Module\PasswordBroker\Application\EntryField\DTO\EntryFieldResponse\EntryFieldNoteResponse;
use App\Module\PasswordBroker\Application\EntryField\DTO\EntryFieldResponse\EntryFieldPasswordResponse;
use App\Module\PasswordBroker\Application\EntryField\DTO\EntryFieldResponse\EntryFieldTotpResponse;
use App\Module\PasswordBroker\Application\EntryField\Provider\EntryFieldResponseProvider;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\PasswordBroker\Fixture\EntryFieldFixture;
use Tests\Shared\IntegrationTestCase;

class EntryFieldResponseProviderTest extends IntegrationTestCase
{
    private EntryFieldResponseProvider $entryFieldResponseProvider;

    /**
     * @throws PersistenceException
     */
    #[\Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->entryFieldResponseProvider = EntryFieldResponseProvider::getInstance();

    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_return_entry_field_response(): void
    {
        $entryField = EntryFieldFixture::create();
        $this->entryFieldResponseProvider->response($entryField);
        $this->expectNotToPerformAssertions();
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_return_entry_field_response_for_password_field(): void
    {
        $entryField = EntryFieldFixture::create(attributes: [EntryFieldFixture::TYPE => EntryFieldTypeEnum::PASSWORD]);
        /** @var EntryFieldPasswordResponse $abstractEntryFieldResponse */
        $abstractEntryFieldResponse = $this->entryFieldResponseProvider->response($entryField);
        $this->assertInstanceOf(EntryFieldPasswordResponse::class, $abstractEntryFieldResponse);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_return_entry_field_response_for_totp_field(): void
    {
        $entryField = EntryFieldFixture::create(attributes: [EntryFieldFixture::TYPE => EntryFieldTypeEnum::TOTP]);
        /** @var EntryFieldTotpResponse $abstractEntryFieldResponse */
        $abstractEntryFieldResponse = $this->entryFieldResponseProvider->response($entryField);
        $this->assertInstanceOf(EntryFieldTotpResponse::class, $abstractEntryFieldResponse);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_return_entry_field_response_for_note_field(): void
    {
        $entryField = EntryFieldFixture::create(attributes: [EntryFieldFixture::TYPE => EntryFieldTypeEnum::NOTE]);
        /** @var EntryFieldNoteResponse $abstractEntryFieldResponse */
        $abstractEntryFieldResponse = $this->entryFieldResponseProvider->response($entryField);
        $this->assertInstanceOf(EntryFieldNoteResponse::class, $abstractEntryFieldResponse);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_return_entry_field_response_for_file_field(): void
    {
        $entryField = EntryFieldFixture::create(attributes: [EntryFieldFixture::TYPE => EntryFieldTypeEnum::FILE]);
        /** @var EntryFieldFileResponse $abstractEntryFieldResponse */
        $abstractEntryFieldResponse = $this->entryFieldResponseProvider->response($entryField);
        $this->assertInstanceOf(EntryFieldFileResponse::class, $abstractEntryFieldResponse);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_return_entry_field_response_for_link_field(): void
    {
        $entryField = EntryFieldFixture::create(attributes: [EntryFieldFixture::TYPE => EntryFieldTypeEnum::LINK]);
        /** @var EntryFieldLinkResponse $abstractEntryFieldResponse */
        $abstractEntryFieldResponse = $this->entryFieldResponseProvider->response($entryField);
        $this->assertInstanceOf(EntryFieldLinkResponse::class, $abstractEntryFieldResponse);
    }
}
