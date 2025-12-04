<?php

namespace App\Module\Identity\Application\User\Job;

use App\Module\Identity\Application\User\Event\UserDeletedEvent;
use App\Module\Identity\Domain\User\Service\UserDomainService;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Shared\Application\Job\AbstractReplicableSyncJob;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use InvalidArgumentException;
use Throwable;

class DeleteUserSyncJob extends AbstractReplicableSyncJob
{
    /**
     * @return void
     * @throws Throwable
     */
    public function handle(): void
    {
        $userDomainService = UserDomainService::getInstance();
        $user = $userDomainService->findUserById(UserId::fromRaw($this->payload['id']));
        if (!$user) {
            throw new InvalidArgumentException('User not found');
        }

        $userDomainService->delete($user);

        EventDispatcher::getInstance()->dispatch(new UserDeletedEvent($user));
    }

}