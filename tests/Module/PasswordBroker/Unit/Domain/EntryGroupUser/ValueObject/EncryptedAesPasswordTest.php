<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Unit\Domain\EntryGroupUser\ValueObject;

use App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject\EncryptedAesPassword;
use InvalidArgumentException;
use stdClass;
use Tests\Shared\UnitTestCase;

class EncryptedAesPasswordTest extends UnitTestCase
{
    public function test_it_should_create_an_encrypted_aes_password(): void
    {
        $rawValue = 'encryptedAesPassword';

        $name = EncryptedAesPassword::fromRaw($rawValue);

        $this->assertSame(EncryptedAesPassword::class, get_class($name));
    }

    public function test_it_should_validate_string(): void
    {
        $this->expectNotToPerformAssertions();

        EncryptedAesPassword::validate('valid string');
    }

    public function test_it_should_throw_exception_for_invalid_data_type_integer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type');

        EncryptedAesPassword::validate(123);
    }

    public function test_it_should_throw_exception_for_invalid_data_type_float(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type');

        EncryptedAesPassword::validate(1.5);
    }

    public function test_it_should_throw_exception_for_invalid_data_type_null(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type');

        EncryptedAesPassword::validate(null);
    }

    public function test_it_should_throw_exception_for_invalid_data_type_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type');

        EncryptedAesPassword::validate(['group name']);
    }

    public function test_it_should_throw_exception_for_invalid_data_type_boolean(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type');

        EncryptedAesPassword::validate(true);
    }

    public function test_it_should_throw_exception_for_invalid_data_type_object(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type');

        EncryptedAesPassword::validate(new stdClass());
    }

    public function tets_it_should_return_origin_value(): void
    {
        $rawValue = 'encryptedAesPassword';

        $name = EncryptedAesPassword::fromRaw($rawValue);

        $this->assertSame($rawValue, $name->toRaw());
    }

    public function test_it_should_return_string(): void
    {
        $name = EncryptedAesPassword::fromRaw('Any string');
        $this->assertIsString($name->toRaw());
    }
}
