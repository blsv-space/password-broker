<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Infrastructure\Entry\Repository;

use App\Module\PasswordBroker\Infrastructure\Entry\Repository\EntryRepository;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\PasswordBroker\Fixture\EntryFixture;
use Tests\Shared\IntegrationTestCase;

class EntryRepositoryTest extends IntegrationTestCase
{
    private EntryRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = EntryRepository::getInstance();
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_save_an_entry(): void
    {
        $entry = EntryFixture::create(persist: false);

        $this->repository->save($entry);

        $this->assertDatabaseHas(EntryFixture::getTableName(), [
            EntryFixture::ID => $entry->id->toRaw(),
        ]);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_find_an_entry_by_id(): void
    {
        $entry = EntryFixture::create(persist: true);
        $foundEntry = $this->repository->findById($entry->id);
        $this->assertEquals($entry->id->toRaw(), $foundEntry->id->toRaw());
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_soft_delete_an_entry(): void
    {
        $entry = EntryFixture::create(persist: true);

        $this->repository->softDelete($entry);

        $this->assertNotNull($entry->deletedAt);
        $this->assertDatabaseHas(EntryFixture::getTableName(), [
            EntryFixture::ID => $entry->id->toRaw(),
            EntryFixture::DELETED_AT => $entry->deletedAt->toRaw(),
        ]);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_restore_an_entry(): void
    {
        $entry = EntryFixture::create(persist: true);
        $this->repository->softDelete($entry);
        $this->assertNotNull($entry->deletedAt);
        $this->assertDatabaseHas(EntryFixture::getTableName(), [
            EntryFixture::ID => $entry->id->toRaw(),
            EntryFixture::DELETED_AT => $entry->deletedAt->toRaw(),
        ]);

        $this->repository->restore($entry);

        /**
         * @psalm-suppress TypeDoesNotContainNull
         */
        $this->assertNull($entry->deletedAt);
        $this->assertDatabaseHas(EntryFixture::getTableName(), [
            EntryFixture::ID => $entry->id->toRaw(),
            EntryFixture::DELETED_AT => null,
        ]);
    }



    /**
     * @throws PersistenceException
     */
    public function test_it_can_get_all_entries(): void
    {
        EntryFixture::create(persist: true);
        EntryFixture::create(persist: true);

        $entries = $this->repository->findAll();

        $this->assertCount(2, $entries);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_find_entry_by_title(): void
    {
        $entry = EntryFixture::create(persist: true);

        $foundEntry = $this->repository->findEntryByTitle($entry->title);

        $this->assertEquals($entry->id->toRaw(), $foundEntry->id->toRaw());
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_map_array_to_entity(): void
    {
        $entry = EntryFixture::create();
        $mappedEntry = $this->repository->mapArrayToEntity($entry->getAsArray());
        $this->assertEquals($entry->id->toRaw(), $mappedEntry->id->toRaw());
        $this->assertEquals($entry->title, $mappedEntry->title);
        $this->assertEquals($entry->entryGroupId, $mappedEntry->entryGroupId);
    }
}
