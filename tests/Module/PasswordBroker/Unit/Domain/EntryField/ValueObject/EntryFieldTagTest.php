<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Unit\Domain\EntryField\ValueObject;

use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTag;
use InvalidArgumentException;
use Tests\Shared\UnitTestCase;

class EntryFieldTagTest extends UnitTestCase
{
    public function test_it_should_create_instance_from_valid_string(): void
    {
        $rawValue = $this->faker->password(EntryFieldTag::TAG_LENGTH, EntryFieldTag::TAG_LENGTH);

        $name = EntryFieldTag::fromRaw($rawValue);

        $this->assertSame(EntryFieldTag::class, get_class($name));
    }

    public function test_it_should_validate_tag(): void
    {
        $this->expectNotToPerformAssertions();

        EntryFieldTag::validate($this->faker->password(EntryFieldTag::TAG_LENGTH, EntryFieldTag::TAG_LENGTH));
    }

    public function test_it_should_throw_exception_for_invalid_data_type_integer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type');

        EntryFieldTag::validate(123);
    }
    public function test_it_should_throw_exception_for_invalid_data_length(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid tag length');

        EntryFieldTag::validate($this->faker->password(EntryFieldTag::TAG_LENGTH - 1, EntryFieldTag::TAG_LENGTH - 1));
    }
}
