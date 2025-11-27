<?php

namespace Tests\Module\Identity\Unit\Domain\RefreshToken\ValueObject;

use App\Module\Identity\Domain\RefreshToken\ValueObject\RefreshTokenId;
use Tests\Shared\UnitTestCase;

class RefreshTokenIdTest extends UnitTestCase
{
    /**
     * @return void
     */
    public function testItShouldCreateARefreshTokenId(): void
    {
        $id = RefreshTokenId::generate()->toRaw();
        $refreshTokenId = RefreshTokenId::fromRaw($id);
        $this->assertEquals($id, $refreshTokenId->toRaw());
    }

}