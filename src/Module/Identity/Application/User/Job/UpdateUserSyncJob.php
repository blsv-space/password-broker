<?php

namespace App\Module\Identity\Application\User\Job;

use App\Module\Identity\Application\User\Event\UserUpdatedEvent;
use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\AuthDomainService;
use App\Module\Identity\Domain\User\Service\UserDomainService;
use App\Module\Identity\Domain\User\Validator\PasswordValidator;
use App\Module\Identity\Domain\User\ValueObject\HashedPassword;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\Identity\Domain\User\ValueObject\UserName;
use App\Shared\Application\Job\AbstractReplicableSyncJob;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use InvalidArgumentException;
use Throwable;

class UpdateUserSyncJob extends AbstractReplicableSyncJob
{
    /**
     * @return User
     * @throws Throwable
     */
    public function handle(): User
    {
        $userDomainService = UserDomainService::getInstance();
        $authApplicationService = AuthDomainService::getInstance();
        $this->validate();
        $user = $userDomainService->findUserById(UserId::fromRaw($this->payload['id']));
        if (!$user) {
            throw new InvalidArgumentException('User not found');
        }
        if (!empty($this->payload['password'])) {
            new PasswordValidator()->validate($this->payload['password']);
            $user->hashedPassword = HashedPassword::fromRaw(
                $authApplicationService->hashPassword($this->payload['password'])
            );
        }
        $user->userName = UserName::fromRaw($this->payload['userName']);
        $userDomainService->save($user);

        EventDispatcher::getInstance()->dispatch(new UserUpdatedEvent($user));

        return $user;
    }

    /**
     * @return void
     */
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