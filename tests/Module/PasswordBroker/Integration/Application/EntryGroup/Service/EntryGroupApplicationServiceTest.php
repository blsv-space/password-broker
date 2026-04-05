<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryGroup\Service;

use App\Module\PasswordBroker\Application\EntryGroup\Service\EntryGroupApplicationService;
use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\Repository\EntryGroupRepository;
use App\Shared\Domain\ValueObject\DateTime;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryOperatorEnum;
use InvalidArgumentException;
use Tests\Module\PasswordBroker\Fixture\EntryGroupFixture;
use Tests\Shared\IntegrationTestCase;
use Throwable;

class EntryGroupApplicationServiceTest extends IntegrationTestCase
{
    /**
     * @throws Throwable
     * @throws PersistenceException
     */
    public function test_it_should_create_an_entry_group(): void
    {
        $entryGroupApplicationService = EntryGroupApplicationService::getInstance();

        $name = $this->faker->word();
        $entryGroupApplicationService->createEntryGroupSync(
            name: $name,
        );

        $this->assertDatabaseHas(
            table: EntryGroupFixture::getTableName(),
            param: [
                EntryGroupFixture::NAME => $name,
            ],
        );
    }

    /**
     * @throws Throwable
     */
    public function test_it_should_throw_an_exception_when_trying_to_create_an_entry_group_with_empty_name(): void
    {
        $entryGroupApplicationService = EntryGroupApplicationService::getInstance();

        $this->expectException(InvalidArgumentException::class);
        $entryGroupApplicationService->createEntryGroupSync(
            name: '',
        );
    }

    /**
     * @throws Throwable
     * @throws PersistenceException
     */
    public function test_it_should_create_an_child_entry_group(): void
    {
        $entryGroupApplicationService = EntryGroupApplicationService::getInstance();

        $parentEntryGroup = EntryGroupFixture::create(persist: true);
        $name = $parentEntryGroup->name->toRaw() . '_child';
        $entryGroupApplicationService->createEntryGroupSync(
            name: $name,
            parentEntryGroup: $parentEntryGroup,
        );

        $this->assertDatabaseHas(
            table: EntryGroupFixture::getTableName(),
            param: [
                EntryGroupFixture::NAME => $name,
                EntryGroupFixture::PARENT_ENTRY_GROUP_ID => $parentEntryGroup->id->toRaw(),
            ],
        );
    }

    /**
     * @throws Throwable
     * @throws PersistenceException
     */
    public function test_it_should_create_an_entry_group_from_primitives(): void
    {
        $entryGroupApplicationService = EntryGroupApplicationService::getInstance();
        $name = $this->faker->word();
        $parentEntryGroupId = EntryGroupFixture::create(persist: true)->id->toRaw();
        $entryGroupApplicationService->createEntryGroupFromPrimitivesSync(
            name: $name,
            parentEntryGroupId: $parentEntryGroupId,
        );

        $this->assertDatabaseHas(
            table: EntryGroupFixture::getTableName(),
            param: [
                EntryGroupFixture::NAME => $name,
                EntryGroupFixture::PARENT_ENTRY_GROUP_ID => $parentEntryGroupId,
            ],
        );
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_rename_an_entry_group(): void
    {
        $entryGroupApplicationService = EntryGroupApplicationService::getInstance();
        $entryGroup = EntryGroupFixture::create(persist: true);
        $newName = $entryGroup->name->toRaw() . '_new';

        $entryGroupApplicationService->renameEntryGroupSync($entryGroup->id->toRaw(), $newName);

        $this->assertDatabaseHas(
            table: EntryGroupFixture::getTableName(),
            param: [
                EntryGroupFixture::ID => $entryGroup->id->toRaw(),
                EntryGroupFixture::NAME => $newName,
            ],
        );
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_delete_an_entry_group(): void
    {
        $entryGroupApplicationService = EntryGroupApplicationService::getInstance();
        $entryGroup = EntryGroupFixture::create(persist: true);
        $entryGroupApplicationService->deleteEntryGroupSync($entryGroup->id->toRaw());
        $this->assertDatabaseMissing(
            table: EntryGroupFixture::getTableName(),
            param: [
                EntryGroupFixture::ID => $entryGroup->id->toRaw(),
                EntryGroupFixture::DELETED_AT => null,
            ],
        );
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_move_an_entry_group(): void
    {
        $entryGroupApplicationService = EntryGroupApplicationService::getInstance();
        $entryGroup = EntryGroupFixture::create(persist: true);
        $newParentEntryGroup = EntryGroupFixture::create(persist: true);
        $entryGroupApplicationService->moveEntryGroupSync($entryGroup->id->toRaw(), $newParentEntryGroup->id->toRaw());
        $this->assertDatabaseHas(
            table: EntryGroupFixture::getTableName(),
            param: [
                EntryGroupFixture::ID => $entryGroup->id->toRaw(),
                EntryGroupFixture::PARENT_ENTRY_GROUP_ID => $newParentEntryGroup->id->toRaw(),
            ],
        );
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_search_entry_groups(): void
    {
        $entryGroupApplicationService = EntryGroupApplicationService::getInstance();
        $search = $this->faker->word();

        $nameA = $search . 'A';
        $nameB = $search . 'B';

        $shouldBeFound = [$nameA, $nameB];

        $nameC = $this->faker->word();

        EntryGroupFixture::create(attributes: [EntryGroupFixture::NAME => $nameA], persist: true);
        EntryGroupFixture::create(attributes: [EntryGroupFixture::NAME => $nameB], persist: true);
        EntryGroupFixture::create(attributes: [EntryGroupFixture::NAME => $nameC], persist: true);

        $result = $entryGroupApplicationService->search(query: $search);

        $this->assertCount(2, $result);
        $foundNames = array_map(fn(EntryGroup $entryGroup) => $entryGroup->name->toRaw(), $result);
        sort($foundNames);
        $this->assertEquals($shouldBeFound, $foundNames);

        $result = $entryGroupApplicationService->search(query: $search, limit: 1);

        $this->assertCount(1, $result);
        $foundNames = array_map(fn(EntryGroup $entryGroup) => $entryGroup->name->toRaw(), $result);
        sort($foundNames);
        $this->assertEquals([$shouldBeFound[0]], $foundNames);

        $result = $entryGroupApplicationService->search(query: $search, orderBy: [
            EntryGroupFixture::NAME => 'DESC',
        ]);

        $this->assertCount(2, $result);
        $foundNames = array_map(fn(EntryGroup $entryGroup) => $entryGroup->name->toRaw(), $result);
        $this->assertEquals(array_reverse($shouldBeFound), $foundNames);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_get_entry_group_by_uuid(): void
    {
        $entryGroupApplicationService = EntryGroupApplicationService::getInstance();
        $name = $this->faker->word();
        $entryGroup = EntryGroupFixture::create(attributes: [EntryGroupFixture::NAME => $name], persist: true);
        $result = $entryGroupApplicationService->getEntryGroupByUuid(uuid: $entryGroup->id->toRaw());
        $this->assertEquals($entryGroup->id->toRaw(), $result->id->toRaw());
        $this->assertEquals($name, $result->name->toRaw());
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_count_entry_groups_by(): void
    {
        $entryGroupApplicationService = EntryGroupApplicationService::getInstance();
        $deletedAt = $this->faker->dateTime()->format(DateTime::FORMAT);
        EntryGroupFixture::createMany(count: 5, attributes: [EntryGroupFixture::DELETED_AT => $deletedAt]);
        EntryGroupFixture::createMany(count: 2);

        $countEntryGroupsBy = $entryGroupApplicationService->countEntryGroupsBy(
            [
                new QueryCriteria(
                    field: EntryGroupRepository::FIELD_DELETED_AT,
                    value: $deletedAt,
                    operator: QueryOperatorEnum::EQUALS,
                ),
            ],
        );

        $this->assertEquals(5, $countEntryGroupsBy);

        $countEntryGroupsBy = $entryGroupApplicationService->countEntryGroupsBy(
            [
                new QueryCriteria(
                    field: EntryGroupRepository::FIELD_DELETED_AT,
                    value: null,
                    operator: QueryOperatorEnum::EQUALS,
                ),
            ],
        );

        $this->assertEquals(2, $countEntryGroupsBy);
    }

    public function test_it_should_get_entry_groups_as_tree(): void
    {
        $entryGroupApplicationService = EntryGroupApplicationService::getInstance();
        EntryGroupFixture::createTree(depth: 2, branchesPerLevel: 2);
        $entryGroupTreeNodes = $entryGroupApplicationService->getEntryGroupsAsTree();
        $this->assertCount(1, $entryGroupTreeNodes);
        $this->assertCount(2, $entryGroupTreeNodes[array_key_first($entryGroupTreeNodes)]->children);
        $this->assertCount(2, $entryGroupTreeNodes[array_key_first($entryGroupTreeNodes)]
            ->children[array_key_first($entryGroupTreeNodes[array_key_first($entryGroupTreeNodes)]->children)]->children);
    }
}
