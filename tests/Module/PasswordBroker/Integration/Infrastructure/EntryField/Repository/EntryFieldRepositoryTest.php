<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Infrastructure\EntryField\Repository;

use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\PasswordBroker\Fixture\EntryFieldFixture;
use Tests\Shared\IntegrationTestCase;

class EntryFieldRepositoryTest extends IntegrationTestCase
{
    private EntryFieldRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = EntryFieldRepository::getInstance();
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_save_an_entry_field(): void
    {
        $entryField = EntryFieldFixture::create(persist: false);

        $this->repository->save($entryField);

        $this->assertDatabaseHas(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ID => $entryField->id->toRaw(),
        ]);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_find_an_entry_field_by_id(): void
    {
        $entryField = EntryFieldFixture::create(persist: true);
        $foundEntryField = $this->repository->findById($entryField->id);
        $this->assertEquals($entryField->id->toRaw(), $foundEntryField->id->toRaw());
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_soft_delete_an_entry_field(): void
    {
        $entryField = EntryFieldFixture::create(persist: true);

        $this->repository->softDelete($entryField);

        $this->assertNotNull($entryField->deletedAt);
        $this->assertDatabaseHas(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ID => $entryField->id->toRaw(),
            EntryFieldFixture::DELETED_AT => $entryField->deletedAt->toRaw(),
        ]);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_restore_an_entry_field(): void
    {
        $entryField = EntryFieldFixture::create(persist: true);
        $this->repository->softDelete($entryField);
        $this->assertNotNull($entryField->deletedAt);
        $this->assertDatabaseHas(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ID => $entryField->id->toRaw(),
            EntryFieldFixture::DELETED_AT => $entryField->deletedAt->toRaw(),
        ]);

        $this->repository->restore($entryField);

        /**
         * @psalm-suppress TypeDoesNotContainNull
         */
        $this->assertNull($entryField->deletedAt);
        $this->assertDatabaseHas(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ID => $entryField->id->toRaw(),
            EntryFieldFixture::DELETED_AT => null,
        ]);
    }



    /**
     * @throws PersistenceException
     */
    public function test_it_can_get_all_entry_fields(): void
    {
        EntryFieldFixture::create(persist: true);
        EntryFieldFixture::create(persist: true);

        $entryFields = $this->repository->findAll();

        $this->assertCount(2, $entryFields);
    }


    /**
     * @throws PersistenceException
     */
    public function test_it_should_map_array_to_entity(): void
    {
        $entryField = EntryFieldFixture::create();
        $mappedEntry = $this->repository->mapArrayToEntity($entryField->getAsArray());
        $this->assertEquals($entryField->id->toRaw(), $mappedEntry->id->toRaw());
        $this->assertEquals($entryField->title, $mappedEntry->title);
        $this->assertEquals($entryField->entryId, $mappedEntry->entryId);
    }
}
