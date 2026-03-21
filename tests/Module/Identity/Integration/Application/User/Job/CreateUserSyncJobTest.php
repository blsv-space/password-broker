<?php

declare(strict_types=1);

namespace Tests\Module\Identity\Integration\Application\User\Job;

use App\Module\Identity\Application\User\Event\UserCreatedEvent;
use App\Module\Identity\Application\User\Job\CreateUserSyncJob;
use App\Module\Identity\Domain\User\Service\RsaDomainService;
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
     * @throws Throwable
     */
    public function test_handle_creates_and_saves_user(): void
    {
        $rsaKeyPair = RsaDomainService::getInstance()->generateKeyPair($this->faker->password());

        $payload = [
            CreateUserSyncJob::PAYLOAD_KEY_ID => UserId::generate()->toRaw(),
            CreateUserSyncJob::PAYLOAD_KEY_USER_NAME => $this->faker->userName(),
            CreateUserSyncJob::PAYLOAD_KEY_PASSWORD => $this->faker->password(),
            CreateUserSyncJob::PAYLOAD_KEY_EMAIL => $this->faker->email(),
            CreateUserSyncJob::PAYLOAD_KEY_IS_ADMIN => $this->faker->boolean(),
            CreateUserSyncJob::PAYLOAD_KEY_RSA_PRIVATE_KEY => $rsaKeyPair->privateKey,
            CreateUserSyncJob::PAYLOAD_KEY_RSA_PUBLIC_KEY => $rsaKeyPair->publicKey,
        ];

        $createUserSyncJob = new CreateUserSyncJob($payload);

        $createUserSyncJob->handle();

        $this->assertDatabaseHas(UserFixture::getTableName(), ['userName' => $payload['userName']]);
    }

    /**
     * @throws Throwable
     * @throws PersistenceException
     */
    public function test_handle_throws_exception_if_user_already_exists(): void
    {
        $rsaKeyPair = RsaDomainService::getInstance()->generateKeyPair($this->faker->password());

        $payload = [
            CreateUserSyncJob::PAYLOAD_KEY_ID => UserId::generate()->toRaw(),
            CreateUserSyncJob::PAYLOAD_KEY_USER_NAME => $this->faker->userName(),
            CreateUserSyncJob::PAYLOAD_KEY_PASSWORD => $this->faker->password(),
            CreateUserSyncJob::PAYLOAD_KEY_EMAIL => $this->faker->email(),
            CreateUserSyncJob::PAYLOAD_KEY_IS_ADMIN => $this->faker->boolean(),
            CreateUserSyncJob::PAYLOAD_KEY_RSA_PRIVATE_KEY => $rsaKeyPair->privateKey,
            CreateUserSyncJob::PAYLOAD_KEY_RSA_PUBLIC_KEY => $rsaKeyPair->publicKey,
        ];
        UserFixture::create([UserFixture::USER_NAME => $payload['userName']], true);
        $this->expectException(PDOException::class);
        $createUserSyncJob = new CreateUserSyncJob($payload);
        $createUserSyncJob->handle();

    }

    /**
     * @throws Throwable
     */
    public function test_handle_creates_and_saves_user_should_dispatch_event(): void
    {
        $rsaKeyPair = RsaDomainService::getInstance()->generateKeyPair($this->faker->password());

        $payload = [
            CreateUserSyncJob::PAYLOAD_KEY_ID => UserId::generate()->toRaw(),
            CreateUserSyncJob::PAYLOAD_KEY_USER_NAME => $this->faker->userName(),
            CreateUserSyncJob::PAYLOAD_KEY_PASSWORD => $this->faker->password(),
            CreateUserSyncJob::PAYLOAD_KEY_EMAIL => $this->faker->email(),
            CreateUserSyncJob::PAYLOAD_KEY_IS_ADMIN => $this->faker->boolean(),
            CreateUserSyncJob::PAYLOAD_KEY_RSA_PRIVATE_KEY => $rsaKeyPair->privateKey,
            CreateUserSyncJob::PAYLOAD_KEY_RSA_PUBLIC_KEY => $rsaKeyPair->publicKey,
        ];
        $testEventHandler = new TestEventHandler(
            eventNames: [UserCreatedEvent::class],
        );
        new CreateUserSyncJob($payload)->handle();

        $this->assertTrue($testEventHandler->wasDispatched());

    }
}
