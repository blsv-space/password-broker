<?php

declare(strict_types=1);

namespace Tests\Shared\Mock;

use App\Shared\Infrastructure\Replication\Replicator;

class ReplicatorMock extends Replicator
{
    protected function __construct()
    {
        parent::__construct();
        $this->jobReplicator = new JobReplicatorMock();
    }
}
