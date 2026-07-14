<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Replication;

interface JobReplicatorInterface
{
    public const string FIELD_JOB_CLASS = 'jobClass';
    public const string FIELD_PAYLOAD = 'payload';
    public const string FIELD_TIME = 'time';

    public function replicate(string $jobClass, array $payload): void;

    /**
     * @return mixed
     */
    public function read(int $limit, int $offset): array;
}
