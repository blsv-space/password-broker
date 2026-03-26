<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Application\EntryGroup\Job;

use App\Module\PasswordBroker\Application\EntryGroup\Job\MoveEntryGroupSyncJob;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\PasswordBroker\Fixture\EntryGroupFixture;
use Tests\Shared\IntegrationTestCase;

class MoveEntryGroupSyncJobTest extends IntegrationTestCase
{
    /**
     * @throws PersistenceException
     */
    public function test_it_should_move_an_entry_group(): void
    {
        $tree = EntryGroupFixture::createTree(depth: 2, branchesPerLevel: 1);

        $rootEntryGroup = array_first($tree);
        $lastEntryGroup = array_last($tree);

        $this->assertFalse($rootEntryGroup->equals($lastEntryGroup), 'Entry groups should not be same');

        $this->assertNotEquals(
            $rootEntryGroup->getId()->toRaw(),
            $lastEntryGroup->parentEntryGroupId->toRaw(),
            'Entry groups should not belong to the root entry group',
        );

        $payload = [
            MoveEntryGroupSyncJob::PAYLOAD_KEY_ID => $lastEntryGroup->getId()->toRaw(),
            MoveEntryGroupSyncJob::PAYLOAD_KEY_PARENT_ENTRY_GROUP_ID => $rootEntryGroup->getId()->toRaw(),
        ];

        new MoveEntryGroupSyncJob($payload)->handle();

        $this->assertDatabaseHas(EntryGroupFixture::getTableName(), [
            EntryGroupFixture::ID => $lastEntryGroup->getId()->toRaw(),
            EntryGroupFixture::PARENT_ENTRY_GROUP_ID => $rootEntryGroup->getId()->toRaw(),
        ]);
    }
}
