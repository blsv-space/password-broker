<?php

declare(strict_types=1);

namespace Tests\Module\Identity\Integration\Domain\User\Service;

use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\Identity\Domain\User\Service\RsaDomainService;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use OpenSSLAsymmetricKey;
use Tests\Shared\IntegrationTestCase;

class RsaDomainServiceTest extends IntegrationTestCase
{
    public function test_generate_key_pair(): void
    {
        $password = $this->faker->password;
        $rsaKeyPair = RsaDomainService::getInstance()->generateKeyPair(masterPassword: $password);
        $this->assertNotEmpty($rsaKeyPair->privateKey);
        $this->assertNotEmpty($rsaKeyPair->publicKey);
    }

    /**
     * @throws RsaDomainServiceException
     */
    public function test_should_store_user_private_key(): void
    {
        $password = $this->faker->password;
        $message = $this->faker->text;
        $userId = UserId::generate();
        $rsaDomainService = RsaDomainService::getInstance();
        $rsaKeyPair = $rsaDomainService->generateKeyPair(masterPassword: $password);
        $publicKey = $rsaDomainService->getPublicKeyFromString($rsaKeyPair->publicKey);
        $this->assertInstanceOf(OpenSSLAsymmetricKey::class, $publicKey);
        $messageEncrypted = $rsaDomainService->encryptByPublic($message, $publicKey);
        $rsaDomainService->storeUserPrivateKeyFromString($userId, $rsaKeyPair->privateKey);
        $keyFromStorage = $rsaDomainService->getUserPrivateKeyString($userId);
        $this->assertNotEmpty($keyFromStorage);
        $privateKey = $rsaDomainService->getPrivateKeyFromString(privateKey: $keyFromStorage, masterPassword: $password);
        $this->assertInstanceOf(OpenSSLAsymmetricKey::class, $privateKey);
        $messageDecrypt = $rsaDomainService->decryptByPrivate($messageEncrypted, $privateKey);
        $this->assertEquals($message, $messageDecrypt);
    }
}
