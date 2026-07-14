<?php

declare(strict_types=1);

namespace Tests\Module\Identity\Integration\Application\User\Job;

use App\Module\Identity\Application\User\Event\UserUpdatedEvent;
use App\Module\Identity\Application\User\Job\UpdateUserSyncJob;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Shared\IntegrationTestCase;
use Tests\Shared\TestEventHandler;
use Throwable;

class UpdateUserSyncJobTest extends IntegrationTestCase
{
    /**
     * @throws PersistenceException
     * @throws Throwable
     */
    public function test_handle_update_user(): void
    {
        $user = UserFixture::create(persist: true);
        $nameNew = $this->faker->userName();

        $this->assertDatabaseHas(
            table: UserFixture::getTableName(),
            param: [
                UserFixture::ID => $user->id->toRaw(),
                UserFixture::USER_NAME => $user->userName->toRaw(),
            ],
        );

        $payload = [
            UpdateUserSyncJob::PAYLOAD_KEY_ID => $user->id->toRaw(),
            UpdateUserSyncJob::PAYLOAD_KEY_USER_NAME => $nameNew,
            UpdateUserSyncJob::PAYLOAD_UPDATED_AT => $user->updatedAt->toRaw(),
        ];

        $testEventHandler = new TestEventHandler(eventNames: [UserUpdatedEvent::class]);

        new UpdateUserSyncJob($payload)->handle();

        $this->assertDatabaseMissing(
            table: UserFixture::getTableName(),
            param: [
                UserFixture::ID => $user->id->toRaw(),
                UserFixture::USER_NAME => $user->userName->toRaw(),
            ],
        );

        $this->assertDatabaseHas(
            table: UserFixture::getTableName(),
            param: [
                UserFixture::ID => $user->id->toRaw(),
                UserFixture::USER_NAME => $nameNew,
            ],
        );
        $this->assertTrue($testEventHandler->wasDispatched());
    }
}
