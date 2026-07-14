<?php

declare(strict_types=1);

namespace Tests\Module\Shared\Integration\Infrastructure\Security\Encryption;

use App\Shared\Domain\Security\Encryption\Exception\DecryptionException;
use App\Shared\Domain\Security\Encryption\Exception\EncryptionException;
use App\Shared\Infrastructure\Security\Encryption\AesDecryptor;
use App\Shared\Infrastructure\Security\Encryption\AesEncryptor;
use Tests\Shared\IntegrationTestCase;

class AesDecryptorTest extends IntegrationTestCase
{
    /**
     * @throws DecryptionException
     * @throws EncryptionException
     */
    public function test_it_should_decrypt_data(): void
    {
        $data = $this->faker->text();
        $password = $this->faker->password();
        $iv = $this->faker->password();

        $aesEncryptedData = AesEncryptor::getInstance()->encrypt($data, $password, $iv);
        $this->assertNotEmpty($aesEncryptedData->encryptedData);
        $this->assertEquals(16, strlen($aesEncryptedData->tag));

        $decryptedData = AesDecryptor::getInstance()->decrypt($aesEncryptedData->encryptedData, $password, $iv, $aesEncryptedData->tag);
        $this->assertEquals($data, $decryptedData);
    }
}
