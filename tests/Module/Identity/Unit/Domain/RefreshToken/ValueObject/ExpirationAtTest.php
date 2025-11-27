<?php

namespace Tests\Module\Identity\Unit\Domain\RefreshToken\ValueObject;

use App\Module\Identity\Domain\RefreshToken\ValueObject\ExpirationAt;
use Tests\Shared\UnitTestCase;

class ExpirationAtTest extends UnitTestCase
{
    /**
     * @return void
     */
    public function testItShouldCreateAnExpirationAtFromDateTime(): void
    {
        $expirationAt = $this->faker->dateTime();
        $expirationAtValueObject = ExpirationAt::fromDateTime($expirationAt);
        $this->assertEquals($expirationAt->format("r"), $expirationAtValueObject->toDateTime()->format("r"));
    }

    /**
     * @return void
     */
    public function testItShouldCreateAnExpirationAtFromRaw(): void
    {
        $expirationAt = $this->faker->dateTime();
        $expirationAtValueObject = ExpirationAt::fromRaw($expirationAt->format(ExpirationAt::FORMAT));
        $this->assertEquals($expirationAt->format("r"), $expirationAtValueObject->toDateTime()->format("r"));
    }

}