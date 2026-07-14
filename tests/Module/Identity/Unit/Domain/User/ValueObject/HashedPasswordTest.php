<?php

declare(strict_types=1);

namespace Tests\Module\Identity\Unit\Domain\User\ValueObject;

use App\Module\Identity\Domain\User\ValueObject\HashedPassword;
use Tests\Shared\UnitTestCase;

final class HashedPasswordTest extends UnitTestCase
{
    public function test_it_should_create_a_hashed_password(): void
    {
        $hash = sha1($this->faker->password());

        $hashedPassword = HashedPassword::fromRaw($hash);

        $this->assertEquals($hash, $hashedPassword->toRaw());
    }

}
