<?php

namespace App\Shared\Application\Job;

use App\Shared\Infrastructure\Replication\Replicator;
use Inquisition\Core\Application\Job\AbstractAsyncJob;
use Throwable;

abstract class AbstractReplicableAsyncJob extends AbstractAsyncJob
    implements JobReplicationInterface
{

    /**
     * @var bool
     */
    protected(set) bool $isReplicated = false {
        get {
            return $this->isReplicated;
        }
    }

    /**
     * @return void
     */
    public function markAsReplicated(): void
    {
        $this->isReplicated = true;
    }

    /**
     * @return void
     */
    public function publish(): void
    {
        Replicator::getInstance()->getReplicator()->replicate(
            jobClass: static::class,
            payload: $this->payload
        );
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function execute(): void
    {
        if (!$this->isReplicated) {
            $this->publish();
        }

        parent::execute();
    }

}