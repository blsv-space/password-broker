<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Infrastructure\EntryGroup\Repository;

use App\Module\PasswordBroker\Infrastructure\EntryGroup\Repository\EntryGroupRepository;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\PasswordBroker\Fixture\EntryGroupFixture;
use Tests\Shared\IntegrationTestCase;

class EntryGroupRepositoryTest extends IntegrationTestCase
{
    private EntryGroupRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = EntryGroupRepository::getInstance();
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_save_an_entry_group(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: false);

        $this->repository->save($entryGroup);

        $this->assertDatabaseHas(EntryGroupFixture::getTableName(), [
            EntryGroupFixture::ID => $entryGroup->id->toRaw(),
        ]);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_find_an_entry_group_by_id(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        $foundEntryGroup = $this->repository->findById($entryGroup->id);
        $this->assertEquals($entryGroup->id->toRaw(), $foundEntryGroup->id->toRaw());
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_soft_delete_an_entry_group(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);

        $this->repository->softDelete($entryGroup);

        $this->assertNotNull($entryGroup->deletedAt);
        $this->assertDatabaseHas(EntryGroupFixture::getTableName(), [
            EntryGroupFixture::ID => $entryGroup->id->toRaw(),
            EntryGroupFixture::DELETED_AT => $entryGroup->deletedAt->toRaw(),
        ]);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_restore_an_entry_group(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        $this->repository->softDelete($entryGroup);
        $this->assertNotNull($entryGroup->deletedAt);
        $this->assertDatabaseHas(EntryGroupFixture::getTableName(), [
            EntryGroupFixture::ID => $entryGroup->id->toRaw(),
            EntryGroupFixture::DELETED_AT => $entryGroup->deletedAt->toRaw(),
        ]);

        $this->repository->restore($entryGroup);

        /**
         * @psalm-suppress TypeDoesNotContainNull
         */
        $this->assertNull($entryGroup->deletedAt);
        $this->assertDatabaseHas(EntryGroupFixture::getTableName(), [
            EntryGroupFixture::ID => $entryGroup->id->toRaw(),
            EntryGroupFixture::DELETED_AT => null,
        ]);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_get_all_entry_groups(): void
    {
        EntryGroupFixture::create(persist: true);
        EntryGroupFixture::create(persist: true);

        $entryGroups = $this->repository->findAll();

        $this->assertCount(2, $entryGroups);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_find_entry_group_by_name(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);

        $foundEntryGroup = $this->repository->findEntryGroupByName($entryGroup->name);

        $this->assertEquals($entryGroup->id->toRaw(), $foundEntryGroup->id->toRaw());
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_find_all_children(): void
    {
        $tree = EntryGroupFixture::createTree(depth: 3, branchesPerLevel: 5);

        $root = array_first($tree);

        $children = $this->repository->findAllChildren($root);

        $this->assertCount(count($tree) - 1, $children);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_map_array_to_entity(): void
    {
        $entryGroup = EntryGroupFixture::create();
        $mappedEntryGroup = $this->repository->mapArrayToEntity($entryGroup->getAsArray());
        $this->assertEquals($entryGroup->name->toRaw(), $mappedEntryGroup->name->toRaw());
        $this->assertEquals($entryGroup->id->toRaw(), $mappedEntryGroup->id->toRaw());
        $this->assertEquals($entryGroup->parentEntryGroupId?->toRaw(), $mappedEntryGroup->parentEntryGroupId?->toRaw());
        $this->assertEquals($entryGroup->materializedPath->toRaw(), $mappedEntryGroup->materializedPath->toRaw());
        $this->assertEquals($entryGroup->createdAt->toRaw(), $mappedEntryGroup->createdAt->toRaw());
        $this->assertEquals($entryGroup->updatedAt->toRaw(), $mappedEntryGroup->updatedAt->toRaw());
        $this->assertEquals($entryGroup->deletedAt?->toRaw(), $mappedEntryGroup->deletedAt?->toRaw());
    }
}
