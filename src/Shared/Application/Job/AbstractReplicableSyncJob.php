<?php

declare(strict_types=1);

namespace App\Shared\Application\Job;

use App\Shared\Infrastructure\Replication\Replicator;
use Inquisition\Core\Application\Job\AbstractSyncJob;
use Throwable;

abstract class AbstractReplicableSyncJob extends AbstractSyncJob implements JobReplicationInterface
{
    public protected(set) bool $isReplicated = false {
        get {
            return $this->isReplicated;
        }
    }

    #[\Override]
    public function markAsReplicated(): void
    {
        $this->isReplicated = true;
    }

    #[\Override]
    public function publish(): void
    {
        Replicator::getInstance()->getReplicator()->replicate(
            jobClass: static::class,
            payload: $this->payload,
        );
    }

    /**
     * @throws Throwable
     */
    #[\Override]
    public function execute(): mixed
    {
        if (!$this->isReplicated) {
            $this->publish();
        }

        return parent::execute();
    }

}
