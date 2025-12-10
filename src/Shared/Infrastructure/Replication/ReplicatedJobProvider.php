<?php

namespace App\Shared\Infrastructure\Replication;

use Inquisition\Foundation\Singleton\SingletonInterface;
use Inquisition\Foundation\Singleton\SingletonTrait;

class ReplicatedJobProvider implements SingletonInterface
{
    use SingletonTrait;

    public function provide(int $limit, int $offset): array
    {
        return Replicator::getInstance()->getReplicator()->read(
            limit: $limit,
            offset: $offset,
        );
    }
}