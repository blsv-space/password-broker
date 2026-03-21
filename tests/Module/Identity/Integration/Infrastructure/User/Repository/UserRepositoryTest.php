<?php

declare(strict_types=1);

namespace Tests\Module\Identity\Integration\Infrastructure\User\Repository;

use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\Identity\Domain\User\ValueObject\UserName;
use App\Module\Identity\Infrastructure\User\Repository\UserRepository;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use JsonException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Shared\IntegrationTestCase;

class UserRepositoryTest extends IntegrationTestCase
{
    private UserRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = UserRepository::getInstance();
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_save_a_user(): void
    {
        $name = $this->faker->name;

        $user = UserFixture::create([UserFixture::USER_NAME => $name]);
        $this->repository->save($user);

        $this->assertDatabaseHas(UserFixture::getTableName(), [
            'userName' => $name,
        ]);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_find_a_user_by_id(): void
    {
        $id = UserId::generate()->toRaw();

        $user = UserFixture::create([UserFixture::ID => $id]);
        $this->repository->save($user);

        $foundUser = $this->repository->findById(UserId::fromRaw($id));

        $this->assertNotNull($foundUser);
        $this->assertEquals($id, $foundUser->getId()?->toRaw());
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_returns_null_when_user_not_found(): void
    {
        $nonExistentId = UserId::generate();

        $foundUser = $this->repository->findById($nonExistentId);

        $this->assertNull($foundUser);
    }

    /**
     * @throws PersistenceException
     * @throws JsonException
     */
    public function test_it_can_find_a_user_by_name(): void
    {
        $userName = UserName::fromRaw($this->faker->userName);
        $user = UserFixture::create([
            UserFixture::USER_NAME => $userName->toRaw(),
        ]);
        $this->repository->save($user);

        $foundUser = $this->repository->findByUserName($userName);

        $this->assertNotNull($foundUser);
        $this->assertTrue($userName->equals($user->userName));
    }

    /**
     * @throws JsonException
     * @throws PersistenceException
     */
    public function test_it_can_update_a_user(): void
    {
        $user = UserFixture::create([], true);
        $userName = $user->userName;
        $userNameUpdated = UserName::fromRaw($userName->toRaw() . ' Updated');
        ;

        $user->userName = $userNameUpdated;
        $this->repository->save($user);

        $updatedUser = $this->repository->findById($user->id);
        $this->assertTrue($userNameUpdated->equals($updatedUser->userName));
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_delete_a_user(): void
    {
        $user = UserFixture::create([], true);

        $userId = $user->id;

        $this->repository->removeById($user);

        $this->assertDatabaseMissing(UserFixture::getTableName(), [
            'id' => $userId->toRaw(),
        ]);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_check_if_user_exists(): void
    {
        $user = UserFixture::create([], true);

        $userId = $user->id;

        $exists = $this->repository->exists($userId);

        $this->assertTrue($exists);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_returns_false_when_user_does_not_exist(): void
    {
        $nonExistentId = UserId::generate();

        $exists = $this->repository->exists($nonExistentId);

        $this->assertFalse($exists);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_find_all_users(): void
    {
        $user1 = UserFixture::create([], true);
        $user2 = UserFixture::create([], true);
        $userIds = [$user1->id->toRaw(), $user2->id->toRaw()];

        /**
         * @var User[] $users
         */
        $users = $this->repository->findAll();

        $this->assertCount(2, $users);
        foreach ($users as $user) {
            $this->assertInstanceOf(UserId::class, $user->id);
            $this->assertTrue(in_array($user->id->toRaw(), $userIds));
        }
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_create_a_user_by_array(): void
    {
        $user = UserFixture::create();
        $userFromService = UserRepository::getInstance()->mapArrayToEntity($user->getAsArray());
        $this->assertEquals($user->userName->toRaw(), $userFromService->userName->toRaw());
    }
}
