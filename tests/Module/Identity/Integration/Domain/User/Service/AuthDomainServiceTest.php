<?php

declare(strict_types=1);

namespace Tests\Module\Identity\Integration\Domain\User\Service;

use App\Module\Identity\Domain\User\Service\AuthDomainService;
use App\Module\Identity\Infrastructure\Security\PasswordHasher;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Shared\IntegrationTestCase;

class AuthDomainServiceTest extends IntegrationTestCase
{
    /**
     * @throws PersistenceException
     */
    public function test_it_should_validate_password_by_user(): void
    {
        $password = $this->faker->password();
        $authDomainService = AuthDomainService::getInstance();
        $passwordHasher = PasswordHasher::getInstance();
        $user = UserFixture::create([UserFixture::HASHED_PASSWORD => $passwordHasher->hash($password)]);
        $this->assertTrue($authDomainService->verifyPasswordByUser(
            user: $user,
            password: $password,
        ));
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_validate_password_by_user_with_invalid_password(): void
    {
        $password = $this->faker->password();
        $authDomainService = AuthDomainService::getInstance();
        $passwordHasher = PasswordHasher::getInstance();
        $user = UserFixture::create([UserFixture::HASHED_PASSWORD => $passwordHasher->hash($password)]);
        $this->assertFalse($authDomainService->verifyPasswordByUser(
            user: $user,
            password: $this->faker->password(),
        ));
    }

}
