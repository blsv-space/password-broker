<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryFieldHistory\Provider;

use App\Module\PasswordBroker\Application\EntryFieldHistory\DTO\EntryFieldHistoryResponse\EntryFieldHistoryLinkResponse;
use App\Module\PasswordBroker\Application\EntryFieldHistory\DTO\EntryFieldHistoryResponse\EntryFieldHistoryNoteResponse;
use App\Module\PasswordBroker\Application\EntryFieldHistory\DTO\EntryFieldHistoryResponse\EntryFieldHistoryPasswordResponse;
use App\Module\PasswordBroker\Application\EntryFieldHistory\DTO\EntryFieldHistoryResponse\EntryFieldHistoryTotpResponse;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Provider\EntryFieldHistoryResponseProvider;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\PasswordBroker\Fixture\EntryFieldHistoryFixture;
use Tests\Shared\IntegrationTestCase;

class EntryFieldHistoryResponseProviderTest extends IntegrationTestCase
{
    private EntryFieldHistoryResponseProvider $entryFieldHistoryResponseProvider;

    /**
     * @throws PersistenceException
     */
    #[\Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->entryFieldHistoryResponseProvider = EntryFieldHistoryResponseProvider::getInstance();
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_return_entry_field_history_response(): void
    {
        $entryFieldHistory = EntryFieldHistoryFixture::create();
        $this->entryFieldHistoryResponseProvider->response($entryFieldHistory);
        $this->expectNotToPerformAssertions();
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_return_entry_field_history_response_for_password_field(): void
    {
        $entryField = EntryFieldHistoryFixture::create(attributes: [EntryFieldHistoryFixture::TYPE => EntryFieldTypeEnum::PASSWORD]);
        /** @var EntryFieldHistoryPasswordResponse $abstractEntryFieldHistoryResponse */
        $abstractEntryFieldHistoryResponse = $this->entryFieldHistoryResponseProvider->response($entryField);
        $this->assertInstanceOf(EntryFieldHistoryPasswordResponse::class, $abstractEntryFieldHistoryResponse);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_return_entry_field_history_response_for_totp_field(): void
    {
        $entryField = EntryFieldHistoryFixture::create(attributes: [EntryFieldHistoryFixture::TYPE => EntryFieldTypeEnum::TOTP]);
        /** @var EntryFieldHistoryTotpResponse $abstractEntryFieldHistoryResponse */
        $abstractEntryFieldHistoryResponse = $this->entryFieldHistoryResponseProvider->response($entryField);
        $this->assertInstanceOf(EntryFieldHistoryTotpResponse::class, $abstractEntryFieldHistoryResponse);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_return_entry_field_history_response_for_note_field(): void
    {
        $entryField = EntryFieldHistoryFixture::create(attributes: [EntryFieldHistoryFixture::TYPE => EntryFieldTypeEnum::NOTE]);
        /** @var EntryFieldHistoryNoteResponse $abstractEntryFieldHistoryResponse */
        $abstractEntryFieldHistoryResponse = $this->entryFieldHistoryResponseProvider->response($entryField);
        $this->assertInstanceOf(EntryFieldHistoryNoteResponse::class, $abstractEntryFieldHistoryResponse);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_return_entry_field_response_for_link_field(): void
    {
        $entryField = EntryFieldHistoryFixture::create(attributes: [EntryFieldHistoryFixture::TYPE => EntryFieldTypeEnum::LINK]);
        /** @var EntryFieldHistoryLinkResponse $abstractEntryFieldHistoryResponse */
        $abstractEntryFieldHistoryResponse = $this->entryFieldHistoryResponseProvider->response($entryField);
        $this->assertInstanceOf(EntryFieldHistoryLinkResponse::class, $abstractEntryFieldHistoryResponse);
    }
}
