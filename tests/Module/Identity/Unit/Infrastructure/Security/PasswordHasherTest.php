<?php

namespace Tests\Module\Identity\Unit\Infrastructure\Security;

use App\Module\Identity\Infrastructure\Security\PasswordHashAlgoEnum;
use App\Module\Identity\Infrastructure\Security\PasswordHasher;
use Tests\Shared\UnitTestCase;

class PasswordHasherTest extends UnitTestCase
{
    /**
     * @return void
     */
    public function testItShouldCreateAPasswordHash(): void
    {
        $password = $this->faker->password();
        $hashedPassword = PasswordHasher::getInstance()->hash($password);
        $this->assertTrue(PasswordHasher::getInstance()->verify($password, $hashedPassword));
    }

    /**
     * @return void
     */
    public function testItShouldCreateAPasswordHashAllAlgo(): void
    {
        foreach (PasswordHashAlgoEnum::cases() as $algoEnum) {
            $password = $this->faker->password();
            $hashedPassword = PasswordHasher::getInstance()->hash($password, $algoEnum);
            $this->assertTrue(
                condition: PasswordHasher::getInstance()->verify($password, $hashedPassword),
                message: "Algo {$algoEnum->name} failed"
            );
        }
    }

    /**
     * @return void
     */
    public function testHashResultsAreNotConstant(): void
    {
        $password = $this->faker->password();
        $hashedPassword = PasswordHasher::getInstance()->hash($password);
        for ($i = 0; $i < 5; $i++) {
            $this->assertNotEquals($hashedPassword, PasswordHasher::getInstance()->hash($password));
        }
    }

}