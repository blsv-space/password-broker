<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Unit\Domain\EntryField\ValueObject;

use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldFileSize;
use InvalidArgumentException;
use Tests\Shared\UnitTestCase;

class EntryFieldFileSizeTest extends UnitTestCase
{
    public function test_it_should_create_instance_from_valid_string(): void
    {
        $rawValue = $this->faker->randomNumber();

        $name = EntryFieldFileSize::fromRaw($rawValue);

        $this->assertSame(EntryFieldFileSize::class, get_class($name));
    }

    public function test_it_should_validate_integer(): void
    {
        $this->expectNotToPerformAssertions();

        EntryFieldFileSize::validate(123);
    }

    public function test_it_should_throw_exception_for_invalid_data_type_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type');

        EntryFieldFileSize::validate('invalid string');
    }
    public function test_it_should_throw_exception_for_invalid_data_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data: must be positive');

        EntryFieldFileSize::validate(-1);
    }
}
