<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Unit\Domain\EntryGroupUser\ValueObject;

use App\Module\PasswordBroker\Domain\EntryGroupUser\Enum\RoleEnum;
use App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject\Role;
use InvalidArgumentException;
use Tests\Shared\UnitTestCase;

class RoleTest extends UnitTestCase
{
    public function test_it_should_create_role(): void
    {
        $role = Role::fromRaw(RoleEnum::ADMIN);

        $this->assertSame(Role::class, get_class($role));
    }

    public function test_it_should_validate_role(): void
    {
        $this->expectNotToPerformAssertions();
        Role::validate(RoleEnum::ADMIN);
        Role::validate(RoleEnum::MEMBER);
        Role::validate(RoleEnum::MODERATOR);
    }

    public function test_it_should_throw_exception_for_invalid_role(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Role::validate('invalid role');
    }
}
