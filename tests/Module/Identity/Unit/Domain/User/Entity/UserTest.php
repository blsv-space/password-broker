<?php

namespace Tests\Module\Identity\Unit\Domain\User\Entity;

use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\ValueObject\HashedPassword;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\Identity\Domain\User\ValueObject\UserName;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\DateTime;
use App\Shared\Domain\ValueObject\UpdatedAt;
use Tests\Shared\UnitTestCase;

final class UserTest extends UnitTestCase
{

    public function testItShouldCreateAUser(): void
    {
        $id = UserId::generate()->toRaw();
        $name = $this->faker->userName();
        $password = sha1($this->faker->password());
        $createdAt = $this->faker->dateTime();
        $updateAt = $this->faker->dateTime();

        $user = new User(
            userName: UserName::fromRaw($name),
            hashedPassword: HashedPassword::fromRaw($password),
            id: UserId::fromRaw($id),
            createdAt: CreatedAt::fromDateTime($createdAt),
            updatedAt: UpdatedAt::fromDateTime($updateAt),
        );

        $this->assertEquals($id, $user->id->toRaw());
        $this->assertEquals($name, $user->userName->toRaw());
        $this->assertEquals($password, $user->hashedPassword->toRaw());
        $this->assertEquals(
            $createdAt->format(DateTime::FORMAT),
            $user->createdAt->toDateTime()->format(DateTime::FORMAT)
        );
        $this->assertEquals(
            $updateAt->format(DateTime::FORMAT),
            $user->updatedAt->toDateTime()->format(DateTime::FORMAT)
        );
    }

}