<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Unit\Domain\EntryGroup\ValueObject;

use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use InvalidArgumentException;
use Tests\Shared\UnitTestCase;

class EntryGroupIdTest extends UnitTestCase
{
    public function test_it_should_create_an_entry_group_id(): void
    {
        $rawValue = '019d379d-f4c2-7cc0-bb22-b98ec14369f5';
        $id = EntryGroupId::fromRaw($rawValue);
        $this->assertInstanceOf(EntryGroupId::class, $id);
    }

    public function test_it_should_return_string(): void
    {
        $id = EntryGroupId::fromRaw('019d379d-f4c2-7cc0-bb22-b98ec14369f5');
        $this->assertIsString($id->toRaw());
    }

    public function test_it_should_throw_exception_for_invalid_uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntryGroupId::fromRaw('invalid-uuid');
    }

    public function test_it_should_throw_exception_for_empty_uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntryGroupId::fromRaw('');
    }

    public function test_it_should_throw_exception_for_null_uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntryGroupId::fromRaw(null);
    }
    public function test_it_should_throw_exception_for_non_string_uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntryGroupId::fromRaw(123);
    }
    public function test_it_should_throw_exception_for_non_string_uuid_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntryGroupId::fromRaw(['invalid-uuid']);
    }

    public function test_it_should_throw_exception_for_non_string_uuid_object(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntryGroupId::fromRaw(new \stdClass());
    }

    public function test_it_should_throw_exception_for_non_string_uuid_bool(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntryGroupId::fromRaw(true);
    }

    public function test_it_should_throw_exception_for_non_string_uuid_float(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntryGroupId::fromRaw(1.5);
    }

    public function test_it_should_throw_exception_for_non_string_uuid_int(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntryGroupId::fromRaw(123);
    }
    public function test_it_should_throw_exception_for_non_string_uuid_null(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntryGroupId::fromRaw(null);
    }

    public function test_it_should_return_original_uuid_value(): void
    {
        $rawValue = '019d379d-f4c2-7cc0-bb22-b98ec14369f5';
        $id = EntryGroupId::fromRaw($rawValue);
        $this->assertSame($rawValue, $id->toRaw());
    }
}
