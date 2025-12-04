<?php

namespace App\Shared\Application\Job;

use Inquisition\Core\Application\Job\AbstractSyncJob;

abstract class AbstractReplicableSyncJob extends AbstractSyncJob
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

    }

}