<?php

declare(strict_types=1);

namespace Tests\Module\Identity\Unit\Domain\RefreshToken\ValueObject;

use App\Module\Identity\Domain\RefreshToken\ValueObject\ExpirationAt;
use Tests\Shared\UnitTestCase;

class ExpirationAtTest extends UnitTestCase
{
    public function test_it_should_create_an_expiration_at_from_date_time(): void
    {
        $expirationAt = $this->faker->dateTime();
        $expirationAtValueObject = ExpirationAt::fromDateTime($expirationAt);
        $this->assertEquals($expirationAt->format("r"), $expirationAtValueObject->toDateTime()->format("r"));
    }

    public function test_it_should_create_an_expiration_at_from_raw(): void
    {
        $expirationAt = $this->faker->dateTime();
        $expirationAtValueObject = ExpirationAt::fromRaw($expirationAt->format(ExpirationAt::FORMAT));
        $this->assertEquals($expirationAt->format("r"), $expirationAtValueObject->toDateTime()->format("r"));
    }

}
