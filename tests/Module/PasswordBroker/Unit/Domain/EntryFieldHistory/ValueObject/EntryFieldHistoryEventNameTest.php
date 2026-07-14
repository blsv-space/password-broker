<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Unit\Domain\EntryFieldHistory\ValueObject;

use App\Module\PasswordBroker\Domain\EntryFieldHistory\ValueObject\EntryFieldHistoryEventName;
use InvalidArgumentException;
use Tests\Shared\UnitTestCase;

class EntryFieldHistoryEventNameTest extends UnitTestCase
{
    public function test_it_should_create_instance_from_valid_string(): void
    {
        $rawValue = $this->faker->word();

        $name = EntryFieldHistoryEventName::fromRaw($rawValue);

        $this->assertSame(EntryFieldHistoryEventName::class, get_class($name));
    }

    public function test_it_should_validate_string(): void
    {
        $this->expectNotToPerformAssertions();

        EntryFieldHistoryEventName::validate('valid string');
    }

    public function test_it_should_throw_exception_for_invalid_data_type_integer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type');

        EntryFieldHistoryEventName::validate(123);
    }
}
