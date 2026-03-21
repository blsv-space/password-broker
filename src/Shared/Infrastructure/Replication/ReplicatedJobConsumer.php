<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Replication;

use App\Shared\Application\Job\JobReplicationInterface;
use App\Shared\Infrastructure\Replication\Exception\ReplicationConsumeValidationException;
use Inquisition\Core\Application\Job\JobInterface;
use Inquisition\Foundation\Singleton\SingletonInterface;
use Inquisition\Foundation\Singleton\SingletonTrait;

class ReplicatedJobConsumer implements SingletonInterface
{
    use SingletonTrait;

    public function consume(string $messageJson): void
    {
        $data = json_decode($messageJson, true);

        try {
            $this->validate($data);
        } catch (ReplicationConsumeValidationException $exception) {
            //@todo log errors
            return;
        }
        /**
         * @var JobReplicationInterface $job
         */
        $job = new $data[JobReplicatorInterface::FIELD_JOB_CLASS]($data[JobReplicatorInterface::FIELD_PAYLOAD]);
        $job->markAsReplicated();
        $job->execute();
    }

    /**
     * @throws ReplicationConsumeValidationException
     */
    private function validate(array $data): void
    {
        foreach ([
            JobReplicatorInterface::FIELD_JOB_CLASS,
            JobReplicatorInterface::FIELD_PAYLOAD,
        ] as $field) {
            if (!array_key_exists($field, $data)) {
                throw new ReplicationConsumeValidationException('missing filed: ' . $field);
            }
        }

        if (!class_exists($data[JobReplicatorInterface::FIELD_JOB_CLASS])) {
            throw new ReplicationConsumeValidationException(
                'Job Class: ' . $data[JobReplicatorInterface::FIELD_JOB_CLASS] . ' does not exists',
            );
        }

        if (!is_subclass_of($data[JobReplicatorInterface::FIELD_JOB_CLASS], JobInterface::class)) {
            throw new ReplicationConsumeValidationException(
                'Job Class: ' . $data[JobReplicatorInterface::FIELD_JOB_CLASS] . ' is not a Job',
            );
        }

        if (!is_array($data[JobReplicatorInterface::FIELD_PAYLOAD])) {
            throw new ReplicationConsumeValidationException(
                JobReplicatorInterface::FIELD_PAYLOAD . ' is not an array',
            );
        }
    }
}
