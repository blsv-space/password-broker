<?php

declare(strict_types=1);

namespace App\Module\Identity\Application\User\Job;

use App\Module\Identity\Application\User\Event\UserDeletedEvent;
use App\Module\Identity\Application\User\Service\UserApplicationService;
use App\Shared\Application\Job\AbstractReplicableSyncJob;
use Inquisition\Core\Infrastructure\Event\EventDispatcher;
use InvalidArgumentException;
use Throwable;

class DeleteUserSyncJob extends AbstractReplicableSyncJob
{
    /**
     * @throws Throwable
     */
    #[\Override]
    public function handle(): void
    {
        $userApplicationService = UserApplicationService::getInstance();
        $user = $userApplicationService->getUserByUuid($this->payload['id']);
        if (!$user) {
            throw new InvalidArgumentException('User not found');
        }

        $userApplicationService->delete($user);

        EventDispatcher::getInstance()->dispatch(new UserDeletedEvent($user));
    }

}
