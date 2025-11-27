<?php

namespace Tests\Module\Identity\Integration\Domain\User\Service;

use App\Module\Identity\Domain\User\Service\UserDomainService;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Shared\IntegrationTestCase;
use Throwable;

class UserDomainServiceTest extends IntegrationTestCase
{
    /**
     * @return void
     * @throws PersistenceException
     * @throws Throwable
     */
    public function testItShouldCreateAUser(): void
    {
        $user = UserFixture::create();
        UserDomainService::getInstance()->save($user);
        $this->assertDatabaseHas(
            table: UserFixture::getTableName(),
            param: [
                UserFixture::USER_NAME => $user->userName->toRaw(),
            ]
        );
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItShouldCreateAUserByArray(): void
    {
        $user = UserFixture::create();
        $userFromService = UserDomainService::getInstance()->mapArrayToEntity($user->getAsArray());
        $this->assertEquals($user->userName->toRaw(), $userFromService->userName->toRaw());
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItShouldFindUserByUsername(): void
    {
        $user = UserFixture::create(persist: true);
        $foundUser = UserDomainService::getInstance()->findUserByUsername($user->userName);
        $this->assertEquals($user->userName->toRaw(), $foundUser->userName->toRaw());
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItShouldFindUserByCriteria(): void
    {
        $user = UserFixture::create(persist: true);
        UserFixture::createMany(
            count: 5,
            persist: true
        );
        $users = UserDomainService::getInstance()->findBy([
            new QueryCriteria(
                field: UserFixture::USER_NAME,
                value: $user->userName->toRaw(),
            ),
            new QueryCriteria(
                field: UserFixture::ID,
                value: $user->id->toRaw(),
            ),
        ]);

        $this->assertCount(1, $users);
        $this->assertEquals($user->id->toRaw(), $users[0]->id->toRaw());
    }

}