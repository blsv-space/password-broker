<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Unit\Domain\EntryFieldHistory\ValueObject;

use App\Module\PasswordBroker\Domain\EntryFieldHistory\ValueObject\EntryFieldHistoryIsDeleted;
use InvalidArgumentException;
use Tests\Shared\UnitTestCase;

class EntryFieldHistoryIsDeletedTest extends UnitTestCase
{
    public function test_it_should_create_instance_from_valid_string(): void
    {
        $rawValue = $this->faker->boolean();

        $name = EntryFieldHistoryIsDeleted::fromRaw($rawValue);

        $this->assertSame(EntryFieldHistoryIsDeleted::class, get_class($name));
    }

    public function test_it_should_validate_string(): void
    {
        $this->expectNotToPerformAssertions();

        EntryFieldHistoryIsDeleted::validate($this->faker->boolean());
    }

    public function test_it_should_throw_exception_for_invalid_data_type_integer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type');

        EntryFieldHistoryIsDeleted::validate(123);
    }
}
