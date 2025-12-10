<?php

namespace App\Shared\Application\Job;

use Inquisition\Core\Application\Job\JobInterface;

interface JobReplicationInterface extends JobInterface
{
    /**
     * @var bool $isReplicated
     */
    public bool $isReplicated {
        get;
    }

    /**
     * @return void
     */
    public function publish(): void;

    /**
     * @return void
     */
    public function markAsReplicated(): void;
}