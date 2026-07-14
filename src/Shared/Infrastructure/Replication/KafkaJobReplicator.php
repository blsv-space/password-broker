<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Replication;

use App\Shared\Infrastructure\Kafka\KafkaConnection;
use RdKafka\Exception;

class KafkaJobReplicator implements JobReplicatorInterface
{
    public const string TOPIC_NAME = 'job.replication';

    #[\Override]
    public function replicate(string $jobClass, array $payload): void
    {
        $message = [
            JobReplicatorInterface::FIELD_JOB_CLASS => $jobClass,
            JobReplicatorInterface::FIELD_PAYLOAD => $payload,
            JobReplicatorInterface::FIELD_TIME => time(),
        ];

        $encoded = json_encode($message);

        KafkaConnection::getInstance()->publish(
            topic: self::TOPIC_NAME,
            message: $encoded,
        );
    }

    /**
     *
     * @throws Exception
     */
    #[\Override]
    public function read(int $limit, int $offset): array
    {
        return KafkaConnection::getInstance()->read(
            topic: self::TOPIC_NAME,
            limit: $limit,
            offset: $offset,
        );
    }

}
