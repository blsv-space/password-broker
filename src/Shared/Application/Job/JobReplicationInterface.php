<?php

namespace App\Shared\Application\Job;

interface JobReplicationInterface
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