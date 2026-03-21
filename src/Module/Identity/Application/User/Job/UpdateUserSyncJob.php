<?php

declare(strict_types=1);

namespace App\Module\Identity\Application\User\Job;

use App\Module\Identity\Application\User\Event\UserUpdatedEvent;
use App\Module\Identity\Application\User\Service\UserApplicationService;
use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Validator\PasswordValidator;
use App\Module\Identity\Domain\User\ValueObject\HashedPassword;
use App\Module\Identity\Domain\User\ValueObject\UserName;
use App\Module\Identity\Infrastructure\Security\PasswordHasher;
use App\Shared\Application\Job\AbstractReplicableSyncJob;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use InvalidArgumentException;
use Throwable;

class UpdateUserSyncJob extends AbstractReplicableSyncJob
{
    /**
     * @throws Throwable
     */
    #[\Override]
    public function handle(): User
    {
        $passwordHasher = PasswordHasher::getInstance();
        $userApplicationService = UserApplicationService::getInstance();
        $this->validate();
        $user = $userApplicationService->getUserByUuid($this->payload['id']);
        if (!$user) {
            throw new InvalidArgumentException('User not found');
        }
        if (!empty($this->payload['password'])) {
            new PasswordValidator()->validate($this->payload['password']);
            $user->hashedPassword = HashedPassword::fromRaw(
                $passwordHasher->hash($this->payload['password']),
            );
        }
        $user->userName = UserName::fromRaw($this->payload['userName']);
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
    }
}
