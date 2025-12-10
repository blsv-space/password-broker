<?php

namespace Tests\Shared\Mock;

use App\Shared\Infrastructure\Replication\JobReplicatorInterface;

class JobReplicatorMock implements JobReplicatorInterface
{
    public array $storage = [];

    /**
     * @param string $jobClass
     * @param array $payload
     * @return void
     */
    public function replicate(string $jobClass, array $payload): void
    {
        $this->storage[] = [
            JobReplicatorInterface::FIELD_JOB_CLASS => $jobClass,
            JobReplicatorInterface::FIELD_PAYLOAD => $payload,
            JobReplicatorInterface::FIELD_TIME => time(),
        ];
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function read(int $limit, int $offset): array
    {
        return array_slice($this->storage, $offset, $limit);
    }
}