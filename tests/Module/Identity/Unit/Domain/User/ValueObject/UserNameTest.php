<?php

namespace Tests\Module\Identity\Unit\Domain\User\ValueObject;

use App\Module\Identity\Domain\User\ValueObject\UserName;
use Tests\Shared\UnitTestCase;

final class UserNameTest extends UnitTestCase
{
    public function testItShouldCreateAUserName(): void
    {
        $name = $this->faker->userName();

        $userName = UserName::fromRaw($name);

        $this->assertEquals($name, $userName->toRaw());
    }
}