<?php

declare(strict_types=1);

namespace Tests\Shared;

use RuntimeException;

class FixtureRegister
{
    /**
     * @var array<class-string<AbstractFixture>>
     */
    private static array $fixtures = [];

    /**
     * @param class-string<AbstractFixture> $fixture Class name of the fixture
     */
    public static function register(string $fixture): void
    {
        self::$fixtures[] = $fixture;
    }

    /**
     * @return array<class-string<AbstractFixture>>
     */
    public static function getFixtures(): array
    {
        return self::$fixtures;
    }

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
