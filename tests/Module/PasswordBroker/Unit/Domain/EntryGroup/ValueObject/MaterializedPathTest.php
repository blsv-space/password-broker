<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Unit\Domain\EntryGroup\ValueObject;

use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\MaterializedPath;
use InvalidArgumentException;
use Tests\Shared\UnitTestCase;

class MaterializedPathTest extends UnitTestCase
{
    public function test_it_should_create_a_materialized_path(): void
    {
        $rawValue = '019d379d-f4c2-7cc0-bb22-b98ec14369f5.019d37a6-b739-7a91-9398-94358666b782';
        $path = MaterializedPath::fromRaw($rawValue);
        $this->assertEquals(MaterializedPath::class, get_class($path));
    }

    public function test_it_should_return_original_value(): void
    {
        $rawValue = '019d379d-f4c2-7cc0-bb22-b98ec14369f5.019d37a6-b739-7a91-9398-94358666b782';
        $path = MaterializedPath::fromRaw($rawValue);
        $this->assertEquals($rawValue, $path->toRaw());
    }

    public function test_it_should_return_string(): void
    {
        $rawValue = '019d379d-f4c2-7cc0-bb22-b98ec14369f5.019d37a6-b739-7a91-9398-94358666b782';
        $path = MaterializedPath::fromRaw($rawValue);
        $this->assertIsString($path->toRaw());
    }

    public function test_it_should_validate_materialized_path(): void
    {
        $rawValue = '019d379d-f4c2-7cc0-bb22-b98ec14369f5.019d37a6-b739-7a91-9398-94358666b782';
        $this->expectNotToPerformAssertions();

        MaterializedPath::validate($rawValue);
    }

    public function test_it_should_throw_exception_for_invalid_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MaterializedPath::validate('invalid string');
    }
}
