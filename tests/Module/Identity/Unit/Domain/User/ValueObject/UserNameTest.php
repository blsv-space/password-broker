<?php

declare(strict_types=1);

namespace Tests\Module\Identity\Unit\Domain\User\ValueObject;

use App\Module\Identity\Domain\User\ValueObject\UserName;
use Tests\Shared\UnitTestCase;

final class UserNameTest extends UnitTestCase
{
    public function test_it_should_create_a_user_name(): void
    {
        $name = $this->faker->userName();

        $userName = UserName::fromRaw($name);

        $this->assertEquals($name, $userName->toRaw());
    }
}
