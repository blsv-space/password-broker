<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Unit\Domain\EntryField\ValueObject;

use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldType;
use InvalidArgumentException;
use Tests\Shared\UnitTestCase;

class EntryFieldTypeTest extends UnitTestCase
{
    public function test_it_should_create_instance_from_valid_type(): void
    {
        $name = EntryFieldType::fromRaw(EntryFieldTypeEnum::NOTE);

        $this->assertSame(EntryFieldType::class, get_class($name));
    }

    public function test_it_should_validate_type(): void
    {
        $this->expectNotToPerformAssertions();

        EntryFieldType::validate(EntryFieldTypeEnum::LINK);
    }

    public function test_it_should_throw_exception_for_invalid_data_type_integer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type');

        EntryFieldType::validate(123);
    }
}
