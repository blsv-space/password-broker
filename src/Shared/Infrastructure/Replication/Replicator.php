<?php

namespace App\Shared\Infrastructure\Replication;

use Inquisition\Foundation\Singleton\SingletonInterface;
use Inquisition\Foundation\Singleton\SingletonTrait;

class Replicator implements SingletonInterface
{
    use SingletonTrait;
    protected JobReplicatorInterface $jobReplicator;

    protected function __construct()
    {
        $this->jobReplicator = new KafkaJobReplicator();
    }

    /**
     * @return JobReplicatorInterface
     */
    public function getReplicator(): JobReplicatorInterface
    {
        return $this->jobReplicator;
    }
}