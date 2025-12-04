<?php

namespace Tests\Module\Identity\Integration\Application\Job;

use App\Module\Identity\Application\User\Event\UserCreatedEvent;
use App\Module\Identity\Application\User\Job\CreateUserSyncJob;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use PDOException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Shared\IntegrationTestCase;
use Tests\Shared\TestEventHandler;
use Throwable;

class CreateUserSyncJobTest extends IntegrationTestCase
{
    /**
     * @return void
     * @throws Throwable
     */
    public function testHandleCreatesAndSavesUser(): void
    {
        $payload = [
            'id' => UserId::generate()->toRaw(),
            'userName' => $this->faker->userName(),
            'password' => $this->faker->password(),
        ];

        $createUserSyncJob = new CreateUserSyncJob($payload);

        $createUserSyncJob->handle();

        $this->assertDatabaseHas(UserFixture::getTableName(), ['userName' => $payload['userName']]);
    }

    /**
     * @return void
     * @throws Throwable
     * @throws PersistenceException
     */
    public function testHandleThrowsExceptionIfUserAlreadyExists(): void
    {
        $payload = [
            'id' => UserId::generate()->toRaw(),
            'userName' => $this->faker->userName(),
            'password' => $this->faker->password(),
        ];
        UserFixture::create([UserFixture::USER_NAME => $payload['userName']], true);
        $this->expectException(PDOException::class);
        $createUserSyncJob = new CreateUserSyncJob($payload);
        $createUserSyncJob->handle();

    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testHandleCreatesAndSavesUserShouldDispatchEvent(): void
    {
        $payload = [
            'id' => UserId::generate()->toRaw(),
            'userName' => $this->faker->userName(),
            'password' => $this->faker->password(),
        ];

        $testEventHandler = new TestEventHandler(
            eventNames: [UserCreatedEvent::class],
        );
        new CreateUserSyncJob($payload)->handle();

        $this->assertTrue($testEventHandler->wasDispatched());

    }
}