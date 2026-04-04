<?php

namespace Tests\Module\Shared\Unit\Infrastructure\Security\Encryption;

use App\Shared\Infrastructure\Security\Encryption\InitialVectorProvider;
use Tests\Shared\UnitTestCase;

class InitialVectorProviderTest extends UnitTestCase
{
    public function test_it_should_get_initial_vector(): void
    {
        $initialVector = InitialVectorProvider::getInstance()->getInitialVector();
        $this->assertEquals(12, strlen($initialVector));
    }
}