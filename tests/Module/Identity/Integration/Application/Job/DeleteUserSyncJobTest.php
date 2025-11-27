<?php

namespace Tests\Module\Identity\Integration\Application\Job;

use App\Module\Identity\Application\User\Event\UserDeletedEvent;
use App\Module\Identity\Application\User\Job\DeleteUserSyncJob;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Shared\IntegrationTestCase;
use Tests\Shared\TestEventHandler;
use Throwable;

class DeleteUserSyncJobTest extends IntegrationTestCase
{
    /**
     * @return void
     * @throws PersistenceException
     * @throws Throwable
     */
    public function testHandleDeleteUser(): void
    {
        $user = UserFixture::create(persist: true);

        $this->assertDatabaseHas(
            table: UserFixture::getTableName(),
            param: [
                UserFixture::ID => $user->id->toRaw(),
                UserFixture::USER_NAME => $user->userName->toRaw(),
            ],
        );

        $payload = [
            UserFixture::ID => $user->id->toRaw(),
        ];

        $testEventHandler = new TestEventHandler(eventNames: [UserDeletedEvent::class]);

        new DeleteUserSyncJob($payload)->handle();

        $this->assertDatabaseMissing(
            table: UserFixture::getTableName(),
            param: [
                UserFixture::ID => $user->id->toRaw(),
            ],
        );

        $this->assertTrue($testEventHandler->wasDispatched());
    }
}