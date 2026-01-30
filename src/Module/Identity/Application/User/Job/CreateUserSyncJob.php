<?php

namespace App\Module\Identity\Application\User\Job;

use App\Module\Identity\Application\User\Event\UserCreatedEvent;
use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\AuthDomainService;
use App\Module\Identity\Domain\User\Service\RsaDomainService;
use App\Module\Identity\Domain\User\Service\UserDomainService;
use App\Module\Identity\Domain\User\Validator\PasswordValidator;
use App\Module\Identity\Infrastructure\Http\Controller\UserController;
use App\Module\Identity\Infrastructure\User\Repository\UserRepository;
use App\Shared\Application\Job\AbstractReplicableSyncJob;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use InvalidArgumentException;
use Throwable;

final class CreateUserSyncJob extends AbstractReplicableSyncJob
{
    const string PAYLOAD_KEY_ID = UserRepository::FIELD_ID;
    const string PAYLOAD_KEY_PASSWORD = UserController::FIELD_PASSWORD;
    const string PAYLOAD_KEY_RSA_PRIVATE_KEY = 'rsaPrivateKey';
    const string PAYLOAD_KEY_RSA_PUBLIC_KEY = UserRepository::FIELD_RSA_PUBLIC_KEY;
    const string PAYLOAD_KEY_USER_NAME = UserRepository::FIELD_USER_NAME;
    const string PAYLOAD_KEY_EMAIL = UserRepository::FIELD_EMAIL;
    const string PAYLOAD_KEY_IS_ADMIN = UserRepository::FIELD_IS_ADMIN;

    /**
     * @return User
     * @throws Throwable
     */
    public function handle(): User
    {
        $this->validate();

        $userDomainService = UserDomainService::getInstance();
        $authApplicationService = AuthDomainService::getInstance();
        $payload = $this->payload;
        $payload['hashedPassword'] = $authApplicationService->hashPassword($this->payload[self::PAYLOAD_KEY_PASSWORD]);
        unset($payload[self::PAYLOAD_KEY_PASSWORD]);

        $user = $userDomainService->mapArrayToEntity($payload);

        $userDomainService->save($user);
        RsaDomainService::getInstance()->storeUserPrivateKeyFromString($user->getId(), $payload[self::PAYLOAD_KEY_RSA_PRIVATE_KEY]);
        EventDispatcher::getInstance()->dispatch(new UserCreatedEvent($user));

        return $user;
    }

    /**
     * @return void
     */
    private function validate(): void
    {
        if (empty($this->payload[self::PAYLOAD_KEY_ID])) {
            throw new InvalidArgumentException('User id is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_PASSWORD])) {
            throw new InvalidArgumentException('Password is required');
        }
        new PasswordValidator()->validate($this->payload[self::PAYLOAD_KEY_PASSWORD]);

        if (empty($this->payload[self::PAYLOAD_KEY_USER_NAME])) {
            throw new InvalidArgumentException('User name is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_RSA_PRIVATE_KEY])) {
            throw new InvalidArgumentException('RSA Private Key is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_RSA_PUBLIC_KEY])) {
            throw new InvalidArgumentException('RSA Private Key is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_EMAIL])) {
            throw new InvalidArgumentException('Email is required');
        }

        if (!isset($this->payload[self::PAYLOAD_KEY_IS_ADMIN])) {
            throw new InvalidArgumentException('Is Admin is required');
        }
        if (!is_bool($this->payload[self::PAYLOAD_KEY_IS_ADMIN])) {
            throw new InvalidArgumentException('Is Admin must be a boolean');
        }

    }
}