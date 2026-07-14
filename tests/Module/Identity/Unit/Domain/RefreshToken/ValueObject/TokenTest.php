<?php

declare(strict_types=1);

namespace Tests\Module\Identity\Unit\Domain\RefreshToken\ValueObject;

use App\Module\Identity\Domain\RefreshToken\ValueObject\Token;
use Tests\Shared\UnitTestCase;

class TokenTest extends UnitTestCase
{
    public function test_it_should_create_a_token(): void
    {
        $token = $this->faker->uuid();
        $tokenValueObject = Token::fromRaw($token);
        $this->assertEquals($token, $tokenValueObject->toRaw());
    }

}
