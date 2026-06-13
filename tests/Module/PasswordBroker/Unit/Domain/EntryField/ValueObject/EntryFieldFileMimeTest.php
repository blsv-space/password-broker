<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Unit\Domain\EntryField\ValueObject;

use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldFileMime;
use InvalidArgumentException;
use Tests\Shared\UnitTestCase;

class EntryFieldFileMimeTest extends UnitTestCase
{
    public function test_it_should_create_instance_from_valid_string(): void
    {
        $rawValue = $this->faker->mimeType();

        $name = EntryFieldFileMime::fromRaw($rawValue);

        $this->assertSame(EntryFieldFileMime::class, get_class($name));
    }

    public function test_it_should_validate_string(): void
    {
        $this->expectNotToPerformAssertions();

        EntryFieldFileMime::validate('valid string');
    }

    public function test_it_should_throw_exception_for_invalid_data_type_integer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type');

        EntryFieldFileMime::validate(123);
    }
}
