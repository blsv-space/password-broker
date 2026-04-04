<?php

namespace Tests\Module\Shared\Integration\Infrastructure\Security\Encryption;

use App\Shared\Infrastructure\Security\Encryption\SystemSaltProvider;
use Tests\Shared\IntegrationTestCase;

class SystemSaltProviderTest extends IntegrationTestCase
{
    public function test_it_should_provide_a_32_byte_salt(): void
    {
        $salt = SystemSaltProvider::getInstance()->getSalt();
        $this->assertEquals(32, strlen($salt));
    }

    public function test_it_should_provide_same_salt_each_time(): void
    {
        $salt1 = SystemSaltProvider::getInstance()->getSalt();
        $salt2 = SystemSaltProvider::getInstance()->getSalt();
        $this->assertEquals($salt1, $salt2);
    }
}