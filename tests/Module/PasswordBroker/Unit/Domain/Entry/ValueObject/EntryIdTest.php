<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Unit\Domain\Entry\ValueObject;

use App\Module\PasswordBroker\Domain\Entry\ValueObject\EntryId;
use InvalidArgumentException;
use Tests\Shared\UnitTestCase;

class EntryIdTest extends UnitTestCase
{
    public function test_it_should_create_an_entry_id(): void
    {
        $rawValue = '019d379d-f4c2-7cc0-bb22-b98ec14369f5';
        $id = EntryId::fromRaw($rawValue);
        $this->assertInstanceOf(EntryId::class, $id);
    }

    public function test_it_should_return_string(): void
    {
        $id = EntryId::fromRaw('019d379d-f4c2-7cc0-bb22-b98ec14369f5');
        $this->assertIsString($id->toRaw());
    }

    public function test_it_should_throw_exception_for_invalid_uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntryId::fromRaw('invalid-uuid');
    }

    public function test_it_should_throw_exception_for_empty_uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntryId::fromRaw('');
    }

    public function test_it_should_throw_exception_for_null_uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntryId::fromRaw(null);
    }
    public function test_it_should_throw_exception_for_non_string_uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntryId::fromRaw(123);
    }
    public function test_it_should_throw_exception_for_non_string_uuid_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntryId::fromRaw(['invalid-uuid']);
    }

    public function test_it_should_throw_exception_for_non_string_uuid_object(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntryId::fromRaw(new \stdClass());
    }

    public function test_it_should_throw_exception_for_non_string_uuid_bool(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntryId::fromRaw(true);
    }

    public function test_it_should_throw_exception_for_non_string_uuid_float(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntryId::fromRaw(1.5);
    }

    public function test_it_should_throw_exception_for_non_string_uuid_int(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntryId::fromRaw(123);
    }
    public function test_it_should_throw_exception_for_non_string_uuid_null(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntryId::fromRaw(null);
    }

    public function test_it_should_return_original_uuid_value(): void
    {
        $rawValue = '019d379d-f4c2-7cc0-bb22-b98ec14369f5';
        $id = EntryId::fromRaw($rawValue);
        $this->assertSame($rawValue, $id->toRaw());
    }
}
