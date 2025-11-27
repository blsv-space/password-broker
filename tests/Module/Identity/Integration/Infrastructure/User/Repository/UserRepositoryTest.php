<?php

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

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = UserRepository::getInstance();
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItCanSaveAUser(): void
    {
        $name = $this->faker->name;

        $user = UserFixture::create([UserFixture::USER_NAME => $name]);
        $this->repository->save($user);

        $this->assertDatabaseHas(UserFixture::getTableName(), [
            'userName' => $name,
        ]);
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItCanFindAUserById(): void
    {
        $id = UserId::generate()->toRaw();

        $user = UserFixture::create([UserFixture::ID => $id]);
        $this->repository->save($user);

        $foundUser = $this->repository->findById(UserId::fromRaw($id));

        $this->assertNotNull($foundUser);
        $this->assertEquals($id, $foundUser->getId()?->toRaw());
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItReturnsNullWhenUserNotFound(): void
    {
        $nonExistentId = UserId::generate();

        $foundUser = $this->repository->findById($nonExistentId);

        $this->assertNull($foundUser);
    }

    /**
     * @return void
     * @throws PersistenceException
     * @throws JsonException
     */
    public function testItCanFindAUserByName(): void
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
     * @return void
     * @throws JsonException
     * @throws PersistenceException
     */
    public function testItCanUpdateAUser(): void
    {
        $user = UserFixture::create([], true);
        $userName = $user->userName;
        $userNameUpdated = UserName::fromRaw($userName->toRaw() . ' Updated');;

        $user->userName = $userNameUpdated;
        $this->repository->save($user);

        $updatedUser = $this->repository->findById($user->id);
        $this->assertTrue($userNameUpdated->equals($updatedUser->userName));
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItCanDeleteAUser(): void
    {
        $user = UserFixture::create([], true);

        $userId = $user->id;

        $this->repository->removeById($user);

        $this->assertDatabaseMissing(UserFixture::getTableName(), [
            'id' => $userId->toRaw(),
        ]);
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItCanCheckIfUserExists(): void
    {
        $user = UserFixture::create([], true);

        $userId = $user->id;

        $exists = $this->repository->exists($userId);

        $this->assertTrue($exists);
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItReturnsFalseWhenUserDoesNotExist(): void
    {
        $nonExistentId = UserId::generate();

        $exists = $this->repository->exists($nonExistentId);

        $this->assertFalse($exists);
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItCanFindAllUsers(): void
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
            $this->assertTrue(in_array($user->id?->toRaw(), $userIds));
        }
    }

}