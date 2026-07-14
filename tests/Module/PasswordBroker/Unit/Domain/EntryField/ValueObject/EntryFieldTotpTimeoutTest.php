<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Unit\Domain\EntryField\ValueObject;

use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTotpTimeout;
use InvalidArgumentException;
use Tests\Shared\UnitTestCase;

class EntryFieldTotpTimeoutTest extends UnitTestCase
{
    public function test_it_should_create_instance_from_valid_integer(): void
    {
        $rawValue = $this->faker->numberBetween(1, 100_000);

        $name = EntryFieldTotpTimeout::fromRaw($rawValue);

        $this->assertSame(EntryFieldTotpTimeout::class, get_class($name));
    }

    public function test_it_should_validate_int(): void
    {
        $this->expectNotToPerformAssertions();

        EntryFieldTotpTimeout::validate($this->faker->numberBetween(1, 100_000));
    }

    public function test_it_should_throw_exception_for_invalid_data_type_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type');

        EntryFieldTotpTimeout::validate("invalid string");
    }
}
