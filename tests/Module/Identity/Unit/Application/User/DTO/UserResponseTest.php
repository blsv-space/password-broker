<?php

namespace Tests\Module\Identity\Unit\Application\User\DTO;

use App\Module\Identity\Application\User\DTO\UserResponse;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Shared\UnitTestCase;

class UserResponseTest extends UnitTestCase
{
    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItShouldCreateAUserResponse(): void
    {
        $user = UserFixture::create();

        $userResponse = UserResponse::fromEntity($user);

        $this->assertArrayHasKey(UserFixture::USER_NAME, $userResponse->getAsArray());
    }
}