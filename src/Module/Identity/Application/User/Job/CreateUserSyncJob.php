<?php

declare(strict_types=1);

namespace App\Module\Identity\Application\User\Job;

use App\Module\Identity\Application\User\Event\UserCreatedEvent;
use App\Module\Identity\Application\User\Service\UserApplicationService;
use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\RsaDomainService;
use App\Module\Identity\Domain\User\Validator\PasswordValidator;
use App\Module\Identity\Infrastructure\User\Repository\UserRepository;
use App\Shared\Application\Job\AbstractReplicableSyncJob;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use InvalidArgumentException;
use Throwable;

final class CreateUserSyncJob extends AbstractReplicableSyncJob
{
    public const string PAYLOAD_KEY_ID = UserRepository::FIELD_ID;
    public const string PAYLOAD_KEY_HASHED_PASSWORD = 'hashedPassword';
    public const string PAYLOAD_KEY_RSA_PRIVATE_KEY = 'rsaPrivateKey';
    public const string PAYLOAD_KEY_RSA_PUBLIC_KEY = UserRepository::FIELD_RSA_PUBLIC_KEY;
    public const string PAYLOAD_KEY_USER_NAME = UserRepository::FIELD_USER_NAME;
    public const string PAYLOAD_KEY_EMAIL = UserRepository::FIELD_EMAIL;
    public const string PAYLOAD_KEY_IS_ADMIN = UserRepository::FIELD_IS_ADMIN;
    public const string PAYLOAD_CREATED_AT = UserRepository::FIELD_CREATED_AT;
    public const string PAYLOAD_UPDATED_AT = UserRepository::FIELD_UPDATED_AT;

    /**
     * @throws Throwable
     */
    #[\Override]
    public function handle(): User
    {
        $userRepository = UserRepository::getInstance();
        $userApplicationService = UserApplicationService::getInstance();
        $this->validate();
        $payload = $this->payload;
        unset($payload['password']);

        $user = $userRepository->mapArrayToEntity($payload);
        $userApplicationService->save($user);

        RsaDomainService::getInstance()->storeUserPrivateKeyFromString($user->getId(), $payload[self::PAYLOAD_KEY_RSA_PRIVATE_KEY]);
        EventDispatcher::getInstance()->dispatch(new UserCreatedEvent($user));

        return $user;
    }

    private function validate(): void
    {
        if (empty($this->payload[self::PAYLOAD_KEY_ID])) {
            throw new InvalidArgumentException('User id is required');
        }

        if (empty($this->payload[self::PAYLOAD_KEY_HASHED_PASSWORD])) {
            throw new InvalidArgumentException('Password is required');
        }
        new PasswordValidator()->validate($this->payload[self::PAYLOAD_KEY_HASHED_PASSWORD]);

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

        if (empty($this->payload[self::PAYLOAD_CREATED_AT])
            || !is_string($this->payload[self::PAYLOAD_CREATED_AT])
        ) {
            throw new InvalidArgumentException('CreatedAt is required');
        }

        if (empty($this->payload[self::PAYLOAD_UPDATED_AT])
            || !is_string($this->payload[self::PAYLOAD_UPDATED_AT])
        ) {
            throw new InvalidArgumentException('UpdatedAt is required');
        }

    }
}
