<?php

declare(strict_types=1);

namespace Tests\Module\Identity\Unit\Domain\User\ValueObject;

use App\Module\Identity\Domain\User\ValueObject\UserId;
use Tests\Shared\UnitTestCase;

final class UserIdTest extends UnitTestCase
{
    public function test_is_should_create_a_user_id(): void
    {
        $id = UserId::generate()->toRaw();

        UserId::fromRaw($id);

        $this->assertEquals($id, UserId::fromRaw($id)->toRaw());
    }
}
