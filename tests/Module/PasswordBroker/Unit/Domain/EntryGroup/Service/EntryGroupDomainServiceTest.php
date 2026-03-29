<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Unit\Domain\EntryGroup\Service;

use App\Module\PasswordBroker\Domain\EntryGroup\Service\EntryGroupDomainService;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\PasswordBroker\Fixture\EntryGroupFixture;
use Tests\Shared\UnitTestCase;

class EntryGroupDomainServiceTest extends UnitTestCase
{
    /**
     * @throws PersistenceException
     */
    public function test_it_should_make_materialized_path(): void
    {
        $entryGroupDomainService = EntryGroupDomainService::getInstance();
        $root = EntryGroupFixture::create(persist: true);

        $child_A = EntryGroupFixture::create(
            attributes: [EntryGroupFixture::PARENT_ENTRY_GROUP => $root],
            persist: true,
        );
        $child_B = EntryGroupFixture::create(
            attributes: [EntryGroupFixture::PARENT_ENTRY_GROUP => $root],
            persist: true,
        );

        $child_A_A = EntryGroupFixture::create(
            attributes: [EntryGroupFixture::PARENT_ENTRY_GROUP => $child_A],
            persist: true,
        );

        $root_2 = EntryGroupFixture::create(persist: true);

        $materializedPathRoot = $entryGroupDomainService->makeMaterializedPath($root->id);
        $this->assertEquals(
            $root->id->toRaw(),
            $materializedPathRoot->toRaw(),
        );

        $materializedPathChild_A = $entryGroupDomainService->makeMaterializedPath($child_A->id, $root);
        $this->assertEquals(
            $root->id->toRaw() . EntryGroupDomainService::MATERIALIZED_PATH_SEPARATOR . $child_A->id->toRaw(),
            $materializedPathChild_A->toRaw(),
        );

        $materializedPathChild_A_A = $entryGroupDomainService->makeMaterializedPath($child_A_A->id, $child_A);
        $this->assertEquals(
            $root->id->toRaw() . EntryGroupDomainService::MATERIALIZED_PATH_SEPARATOR . $child_A->id->toRaw()
            . EntryGroupDomainService::MATERIALIZED_PATH_SEPARATOR . $child_A_A->id->toRaw(),
            $materializedPathChild_A_A->toRaw(),
        );

        $materializedPathChild_B = $entryGroupDomainService->makeMaterializedPath($child_B->id, $root);
        $this->assertEquals(
            $root->id->toRaw() . EntryGroupDomainService::MATERIALIZED_PATH_SEPARATOR . $child_B->id->toRaw(),
            $materializedPathChild_B->toRaw(),
        );

        $materializedPathRoot_2 = $entryGroupDomainService->makeMaterializedPath($root_2->id);
        $this->assertEquals(
            $root_2->id->toRaw(),
            $materializedPathRoot_2->toRaw(),
        );
    }

}
