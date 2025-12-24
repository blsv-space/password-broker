<?php

namespace Tests\Module\Identity\Integration\Application\Service;

use App\Module\Identity\Application\User\Service\UserApplicationService;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Shared\IntegrationTestCase;
use Throwable;

class UserApplicationServiceTest extends IntegrationTestCase
{

    /**
     * @return void
     * @throws PersistenceException
     * @throws Throwable
     */
    public function testItShouldCreateAUser(): void
    {
        $user = UserFixture::create();
        UserApplicationService::getInstance()->createUserSync(
            userName: $user->userName->toRaw(),
            password: $user->hashedPassword->toRaw(),
            email: $user->email->toRaw(),
            masterPassword: $this->faker->password(),
            isAdmin: $user->isAdmin->toRaw(),
        );

        $this->assertDatabaseHas(
            table: UserFixture::getTableName(),
            param: [UserFixture::USER_NAME => $user->userName->toRaw()],
        );
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItShouldGetUserById(): void
    {
        $user = UserFixture::create(
            persist: true,
        );
        $foundUser = UserApplicationService::getInstance()->getUserByUud($user->id->toRaw());
        $this->assertEquals($user->id->toRaw(), $foundUser->id->toRaw());
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItShouldGetUsersBy(): void
    {
        $user = UserFixture::create(
            persist: true
        );
        $usersBy = UserApplicationService::getInstance()->getUsersBy([
            new QueryCriteria(
                field: UserFixture::USER_NAME,
                value: $user->userName->toRaw(),
            )
        ]);

        $this->assertCount(1, $usersBy);
        $this->assertEquals($user->id->toRaw(), $usersBy[0]->id->toRaw());
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItShouldGetUsersByOrder(): void
    {
        $name_1 = 'bbb';
        $name_2 = 'aaa';

        UserFixture::create(
            attributes: [
                UserFixture::USER_NAME => $name_1,
            ],
            persist: true,
        );
        UserFixture::create(
            attributes: [
                UserFixture::USER_NAME => $name_2,
            ],
            persist: true,
        );
        $usersBy = UserApplicationService::getInstance()->getUsersBy(orderBy: [UserFixture::USER_NAME => 'ASC']);
        $this->assertEquals($name_2, $usersBy[0]->userName->toRaw());
        $this->assertCount(2, $usersBy);
    }
}