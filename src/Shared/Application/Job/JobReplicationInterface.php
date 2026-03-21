<?php

declare(strict_types=1);

namespace App\Shared\Application\Job;

use Inquisition\Core\Application\Job\JobInterface;

interface JobReplicationInterface extends JobInterface
{
    public bool $isReplicated {
        get;
    }

    public function publish(): void;

    public function markAsReplicated(): void;
}
