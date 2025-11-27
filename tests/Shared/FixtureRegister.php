<?php

namespace Tests\Shared;

use RuntimeException;

class FixtureRegister
{
    /**
     * @var string[]
     */
    private static array $fixtures = [];

    /**
     * @param string $fixture Class name of the fixture
     * @return void
     */
    public static function register(string $fixture): void
    {
        self::$fixtures[] = $fixture;
    }

    /**
     * @return AbstractFixture[]
     */
    public static function getFixtures(): array
    {
        return self::$fixtures;
    }

    /**
     * @return void
     */
    public static function reset(): void
    {
        foreach (self::$fixtures as $fixture) {
            if (!class_exists($fixture)
                || is_subclass_of($fixture, AbstractFixture::class)
            ) {
                throw new RuntimeException("Fixture $fixture does not exist");
            }

            $fixture::reset();
        }

        self::$fixtures = [];
    }
}