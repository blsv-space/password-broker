<?php

declare(strict_types=1);

namespace Tests\Module\Identity\Unit\Application\User\DTO;

use App\Module\Identity\Application\User\DTO\UserResponse;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Shared\UnitTestCase;

class UserResponseTest extends UnitTestCase
{
    /**
     * @throws PersistenceException
     */
    public function test_it_should_create_a_user_response(): void
    {
        $user = UserFixture::create();

        $userResponse = UserResponse::fromEntity($user);

        $this->assertArrayHasKey(UserFixture::USER_NAME, $userResponse->getAsArray());
    }
}
