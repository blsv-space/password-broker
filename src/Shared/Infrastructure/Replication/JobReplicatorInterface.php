<?php

namespace App\Shared\Infrastructure\Replication;

interface JobReplicatorInterface
{
    public const string FIELD_JOB_CLASS = 'jobClass';
    public const string FIELD_PAYLOAD = 'payload';
    public const string FIELD_TIME = 'time';

    /**
     * @param string $jobClass
     * @param array $payload
     * @return void
     */
    public function replicate(string $jobClass, array $payload): void;

    /**
     * @param int $limit
     * @param int $offset
     * @return mixed
     */
    public function read(int $limit, int $offset): array;
}