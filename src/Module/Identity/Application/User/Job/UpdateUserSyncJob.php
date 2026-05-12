<?php

declare(strict_types=1);

namespace App\Module\Identity\Application\User\Job;

use App\Module\Identity\Application\User\Event\UserUpdatedEvent;
use App\Module\Identity\Application\User\Service\UserApplicationService;
use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\ValueObject\UserName;
use App\Module\Identity\Infrastructure\User\Repository\UserRepository;
use App\Shared\Application\Job\AbstractReplicableSyncJob;
use App\Shared\Domain\ValueObject\UpdatedAt;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use InvalidArgumentException;
use Throwable;

class UpdateUserSyncJob extends AbstractReplicableSyncJob
{
    public const string PAYLOAD_KEY_ID = UserRepository::FIELD_ID;
    public const string PAYLOAD_KEY_HASHED_PASSWORD = 'hashedPassword';
    public const string PAYLOAD_KEY_USER_NAME = UserRepository::FIELD_USER_NAME;
    public const string PAYLOAD_UPDATED_AT = UserRepository::FIELD_UPDATED_AT;

    /**
     * @throws Throwable
     */
    #[\Override]
    public function handle(): User
    {
        $userApplicationService = UserApplicationService::getInstance();
        $this->validate();
        $user = $userApplicationService->getUserByUuid($this->payload['id']);
        if (!$user) {
            throw new InvalidArgumentException('User not found');
        }
        if (!empty($this->payload[self::PAYLOAD_KEY_HASHED_PASSWORD])) {
            $user->hashedPassword = $this->payload[self::PAYLOAD_KEY_HASHED_PASSWORD];
        }
        $user->userName = UserName::fromRaw($this->payload['userName']);
        $user->updatedAt = UpdatedAt::fromRaw($this->payload[self::PAYLOAD_UPDATED_AT]);
        $userApplicationService->update($user);

        EventDispatcher::getInstance()->dispatch(new UserUpdatedEvent($user));

        return $user;
    }

    private function validate(): void
    {
        if (empty($this->payload['userName'])) {
            throw new InvalidArgumentException('User name is required');
        }
        if (empty($this->payload['id'])) {
            throw new InvalidArgumentException('User id is required');
        }
        if (empty($this->payload[self::PAYLOAD_UPDATED_AT])
            || !is_string($this->payload[self::PAYLOAD_UPDATED_AT])
        ) {
            throw new InvalidArgumentException('UpdatedAt is required');
        }
    }
}
