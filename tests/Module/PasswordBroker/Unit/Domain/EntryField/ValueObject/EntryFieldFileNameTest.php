<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Unit\Domain\EntryField\ValueObject;

use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldFileName;
use InvalidArgumentException;
use Tests\Shared\UnitTestCase;

class EntryFieldFileNameTest extends UnitTestCase
{
    public function test_it_should_create_instance_from_valid_string(): void
    {
        $rawValue = $this->faker->mimeType();

        $name = EntryFieldFileName::fromRaw($rawValue);

        $this->assertSame(EntryFieldFileName::class, get_class($name));
    }

    public function test_it_should_validate_string(): void
    {
        $this->expectNotToPerformAssertions();

        EntryFieldFileName::validate('valid string');
    }

    public function test_it_should_throw_exception_for_invalid_data_type_integer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type');

        EntryFieldFileName::validate(123);
    }
}
