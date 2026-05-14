<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Unit\Domain\Entry\ValueObject;

use App\Module\PasswordBroker\Domain\Entry\ValueObject\Title;
use InvalidArgumentException;
use stdClass;
use Tests\Shared\UnitTestCase;

class TitleTest extends UnitTestCase
{
    public function test_it_should_create_instance_from_valid_string(): void
    {
        $rawValue = 'My Entry';

        $name = Title::fromRaw($rawValue);

        $this->assertSame(Title::class, get_class($name));
    }

    public function test_it_should_create_instance_from_empty_string(): void
    {
        $name = Title::fromRaw('');

        $this->assertSame('', $name->toRaw());
    }

    public function test_it_should_validate_string(): void
    {
        $this->expectNotToPerformAssertions();

        Title::validate('valid string');
    }

    public function test_it_should_throw_exception_for_invalid_data_type_integer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type');

        Title::validate(123);
    }

    public function test_it_should_throw_exception_for_invalid_data_type_float(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type');

        Title::validate(1.5);
    }

    public function test_it_should_throw_exception_for_invalid_data_type_null(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type');

        Title::validate(null);
    }

    public function test_it_should_throw_exception_for_invalid_data_type_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type');

        Title::validate(['group name']);
    }

    public function test_it_should_throw_exception_for_invalid_data_type_boolean(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type');

        Title::validate(true);
    }

    public function test_it_should_throw_exception_for_invalid_data_type_object(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type');

        Title::validate(new stdClass());
    }

    public function tets_it_should_return_origin_value(): void
    {
        $rawValue = 'Test Group Name';

        $name = Title::fromRaw($rawValue);

        $this->assertSame($rawValue, $name->toRaw());
    }

    public function test_it_should_return_string(): void
    {
        $name = Title::fromRaw('Any Name');
        $this->assertIsString($name->toRaw());
    }
}
