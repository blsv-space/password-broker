<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Unit\Domain\EntryField\ValueObject;

use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldInitializationVector;
use InvalidArgumentException;
use Tests\Shared\UnitTestCase;

class EntryFieldInitializationVectorTest extends UnitTestCase
{
    public function test_it_should_create_instance_from_valid_string(): void
    {
        $rawValue = $this->faker->password(EntryFieldInitializationVector::IV_LENGTH, EntryFieldInitializationVector::IV_LENGTH);

        $name = EntryFieldInitializationVector::fromRaw($rawValue);

        $this->assertSame(EntryFieldInitializationVector::class, get_class($name));
    }

    public function test_it_should_validate_iv(): void
    {
        $this->expectNotToPerformAssertions();

        EntryFieldInitializationVector::validate($this->faker->password(EntryFieldInitializationVector::IV_LENGTH, EntryFieldInitializationVector::IV_LENGTH));
    }

    public function test_it_should_throw_exception_for_invalid_data_type_integer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type');

        EntryFieldInitializationVector::validate(123);
    }
    public function test_it_should_throw_exception_for_invalid_data_length(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid IV length');

        EntryFieldInitializationVector::validate($this->faker->password(EntryFieldInitializationVector::IV_LENGTH - 1, EntryFieldInitializationVector::IV_LENGTH - 1));
    }
}
