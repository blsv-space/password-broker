<?php

declare(strict_types=1);

namespace Tests\Module\Identity\Unit\Domain\RefreshToken\Entity;

use App\Module\Identity\Domain\RefreshToken\Entity\RefreshToken;
use App\Module\Identity\Domain\RefreshToken\ValueObject\ExpirationAt;
use App\Module\Identity\Domain\RefreshToken\ValueObject\RefreshTokenId;
use App\Module\Identity\Domain\RefreshToken\ValueObject\Token;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Shared\Domain\ValueObject\CreatedAt;
use JsonException;
use Tests\Shared\UnitTestCase;

class RefreshTokenTest extends UnitTestCase
{
    /**
     * @throws JsonException
     */
    public function test_it_should_create_a_refresh_token(): void
    {

        $id = RefreshTokenId::generate();
        $userId = UserId::generate();
        $token = Token::fromRaw($this->faker->sha256());
        $expirationAt = ExpirationAt::fromRaw($this->faker->dateTimeBetween('+1 hour', '+1 day')->format('Y-m-d H:i:s'));
        $createdAt = CreatedAt::fromRaw($this->faker->dateTime()->format('Y-m-d H:i:s'));

        $refreshToken = new RefreshToken(
            userId: $userId,
            token: $token,
            expirationAt: $expirationAt,
            createdAt: $createdAt,
            id: $id,
        );
        $this->assertTrue($refreshToken->id->equals($id));
        $this->assertTrue($refreshToken->userId->equals($userId));
        $this->assertTrue($refreshToken->token->equals($token));
        $this->assertTrue($refreshToken->expirationAt->equals($expirationAt));
        $this->assertTrue($refreshToken->createdAt->equals($createdAt));
    }

}
