<?php

declare(strict_types=1);

namespace Tests\Module\Identity\Unit\Infrastructure\Security;

use App\Module\Identity\Infrastructure\Security\PasswordHashAlgoEnum;
use App\Module\Identity\Infrastructure\Security\PasswordHasher;
use Tests\Shared\UnitTestCase;

class PasswordHasherTest extends UnitTestCase
{
    public function test_it_should_create_a_password_hash(): void
    {
        $password = $this->faker->password();
        $hashedPassword = PasswordHasher::getInstance()->hash($password);
        $this->assertTrue(PasswordHasher::getInstance()->verify($password, $hashedPassword));
    }

    public function test_it_should_create_a_password_hash_all_algo(): void
    {
        foreach (PasswordHashAlgoEnum::cases() as $algoEnum) {
            $password = $this->faker->password();
            $hashedPassword = PasswordHasher::getInstance()->hash($password, $algoEnum);
            $this->assertTrue(
                condition: PasswordHasher::getInstance()->verify($password, $hashedPassword),
                message: "Algo {$algoEnum->name} failed",
            );
        }
    }

    public function test_hash_results_are_not_constant(): void
    {
        $password = $this->faker->password();
        $hashedPassword = PasswordHasher::getInstance()->hash($password);
        for ($i = 0; $i < 5; $i++) {
            $this->assertNotEquals($hashedPassword, PasswordHasher::getInstance()->hash($password));
        }
    }

}
