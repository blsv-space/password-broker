<?php

namespace Identity\Integration\Domain\User\Service;

use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\Identity\Domain\User\Service\RsaDomainService;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use Tests\Shared\IntegrationTestCase;

class RsaDomainServiceTest extends IntegrationTestCase
{
    /**
     * @return void
     */
    public function testGenerateKeyPair(): void
    {
        $password = $this->faker->password;
        $rsaKeyPair = RsaDomainService::getInstance()->generateKeyPair(masterPassword: $password);
        $this->assertNotEmpty($rsaKeyPair->privateKey);
        $this->assertNotEmpty($rsaKeyPair->publicKey);
    }

    /**
     * @return void
     * @throws RsaDomainServiceException
     */
    public function testShouldStoreUserPrivateKey(): void
    {
        $password = $this->faker->password;
        $message = $this->faker->text;
        $userId = UserId::generate();
        $rsaDomainService = RsaDomainService::getInstance();
        $rsaKeyPair = $rsaDomainService->generateKeyPair(masterPassword: $password);
        $publicKey = $rsaDomainService->getPublicKeyFromString($rsaKeyPair->publicKey);
        $messageEncrypted = $publicKey->encrypt($message);
        $rsaDomainService->storeUserPrivateKeyFromString($userId, $rsaKeyPair->privateKey);
        $keyFromStorage = $rsaDomainService->getUserPrivateKey($userId, $password);
        $this->assertNotEmpty($keyFromStorage);
        $privateKey = $rsaDomainService->getPrivateKeyFromString(privateKey: $keyFromStorage, masterPassword: $password);
        $messageDecrypt = $privateKey->decrypt($messageEncrypted);
        $this->assertEquals($message, $messageDecrypt);
    }
}