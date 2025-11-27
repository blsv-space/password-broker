<?php

namespace Tests\Module\Identity\Unit\Domain\User\ValueObject;

use App\Module\Identity\Domain\User\ValueObject\UserId;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Shared\UnitTestCase;

final class UserIdTest extends UnitTestCase
{
    public function testIsShouldCreateAUserId(): void
    {
        $id = UserId::generate()->toRaw();

        UserId::fromRaw($id);

        $this->assertEquals($id, UserId::fromRaw($id)->toRaw());
    }
}