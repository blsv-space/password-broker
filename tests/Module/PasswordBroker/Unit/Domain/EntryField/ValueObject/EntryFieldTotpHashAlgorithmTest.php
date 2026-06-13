<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Unit\Domain\EntryField\ValueObject;

use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTotpHashAlgorithmEnum;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTotpHashAlgorithm;
use InvalidArgumentException;
use Tests\Shared\UnitTestCase;

class EntryFieldTotpHashAlgorithmTest extends UnitTestCase
{
    public function test_it_should_create_instance_from_valid_algo(): void
    {
        $rawValue = EntryFieldTotpHashAlgorithmEnum::SHA1;

        $name = EntryFieldTotpHashAlgorithm::fromRaw($rawValue);

        $this->assertSame(EntryFieldTotpHashAlgorithm::class, get_class($name));
    }

    public function test_it_should_validate_algo(): void
    {
        $this->expectNotToPerformAssertions();

        EntryFieldTotpHashAlgorithm::validate(EntryFieldTotpHashAlgorithmEnum::SHA256);
    }

    public function test_it_should_throw_exception_for_invalid_data_type_integer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data type');

        EntryFieldTotpHashAlgorithm::validate(123);
    }
}
