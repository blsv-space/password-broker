<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Integration\Infrastructure\EntryGroupUser\Repository;

use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\PasswordBroker\Infrastructure\EntryGroupUser\Repository\EntryGroupUserRepository;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\PasswordBroker\Fixture\EntryGroupUserFixture;
use Tests\Shared\IntegrationTestCase;

class EntryGroupUserRepositoryTest extends IntegrationTestCase
{
    private EntryGroupUserRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = EntryGroupUserRepository::getInstance();
    }

    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_map_array_to_entity(): void
    {
        $entryGroupUser = EntryGroupUserFixture::create();
        $entryGroupUserMapped = $this->repository->mapArrayToEntity($entryGroupUser->getAsArray());
        $this->assertEquals($entryGroupUser->id->toRaw(), $entryGroupUserMapped->id->toRaw());
        $this->assertEquals($entryGroupUser->entryGroupId->toRaw(), $entryGroupUserMapped->entryGroupId->toRaw());
        $this->assertEquals($entryGroupUser->userId->toRaw(), $entryGroupUserMapped->userId->toRaw());
        $this->assertEquals($entryGroupUser->role, $entryGroupUserMapped->role);
        $this->assertEquals($entryGroupUser->createdAt->toRaw(), $entryGroupUserMapped->createdAt->toRaw());
        $this->assertEquals($entryGroupUser->updatedAt->toRaw(), $entryGroupUserMapped->updatedAt->toRaw());
    }

    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_find_by_user_id(): void
    {
        $entryGroupUser = EntryGroupUserFixture::create(persist: true);
        EntryGroupUserFixture::create(persist: true);

        $result = $this->repository->findByUserId($entryGroupUser->userId);

        $this->assertCount(1, $result);
        $this->assertEquals($entryGroupUser->id->toRaw(), $result[0]->id->toRaw());
    }

    /**
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_find_by_entry_group_id(): void
    {
        $entryGroupUser = EntryGroupUserFixture::create(persist: true);
        EntryGroupUserFixture::create(persist: true);

        $result = $this->repository->findByEntryGroupId($entryGroupUser->entryGroupId);

        $this->assertCount(1, $result);
        $this->assertEquals($entryGroupUser->id->toRaw(), $result[0]->id->toRaw());
    }

    /**
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_find_by_user_id_and_entry_group_id(): void
    {
        $entryGroupUser = EntryGroupUserFixture::create(persist: true);
        EntryGroupUserFixture::create(persist: true);

        $result = $this->repository->findByUserIdAndEntryGroupId($entryGroupUser->userId, $entryGroupUser->entryGroupId);

        $this->assertNotNull($result);
        $this->assertEquals($entryGroupUser->id->toRaw(), $result->id->toRaw());
    }

    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_return_true_if_user_is_in_entry_group(): void
    {
        $entryGroupUser = EntryGroupUserFixture::create(persist: true);
        $result = $this->repository->isUserInEntryGroup($entryGroupUser->userId, $entryGroupUser->entryGroupId);
        $this->assertTrue($result);
    }
}
