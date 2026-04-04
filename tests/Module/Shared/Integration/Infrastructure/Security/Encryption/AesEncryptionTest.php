<?php

declare(strict_types=1);

namespace Shared\Integration\Infrastructure\Security\Encryption;

use App\Shared\Domain\Security\Encryption\Exception\EncryptionException;
use App\Shared\Infrastructure\Security\Encryption\AesEncryptor;
use Tests\Shared\IntegrationTestCase;

class AesEncryptionTest extends IntegrationTestCase
{
    /**
     * @throws EncryptionException
     */
    public function test_it_should_encrypt_data(): void
    {
        $data = $this->faker->text();
        $password = $this->faker->password();
        $iv = $this->faker->password();

        $aesEncryptedData = AesEncryptor::getInstance()->encrypt($data, $password, $iv);
        $this->assertNotEmpty($aesEncryptedData->encryptedData);
        $this->assertEquals(16, strlen($aesEncryptedData->tag));
    }
}
