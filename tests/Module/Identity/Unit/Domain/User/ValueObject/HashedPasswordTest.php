<?php

namespace Tests\Module\Identity\Unit\Domain\User\ValueObject;

use App\Module\Identity\Domain\User\ValueObject\HashedPassword;
use Tests\Shared\UnitTestCase;

final class HashedPasswordTest extends UnitTestCase
{

    public function testItShouldCreateAHashedPassword(): void
    {
        $hash = sha1($this->faker->password());

        $hashedPassword = HashedPassword::fromRaw($hash);

        $this->assertEquals($hash, $hashedPassword->toRaw());
    }

}