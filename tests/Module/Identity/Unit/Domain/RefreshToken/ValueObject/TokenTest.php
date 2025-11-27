<?php

namespace Tests\Module\Identity\Unit\Domain\RefreshToken\ValueObject;

use App\Module\Identity\Domain\RefreshToken\ValueObject\Token;
use Tests\Shared\UnitTestCase;

class TokenTest extends UnitTestCase
{
    /**
     * @return void
     */
    public function testItShouldCreateAToken(): void
    {
        $token = $this->faker->uuid();
        $tokenValueObject = Token::fromRaw($token);
        $this->assertEquals($token, $tokenValueObject->toRaw());
    }

}