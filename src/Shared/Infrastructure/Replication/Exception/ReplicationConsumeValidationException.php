<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Replication\Exception;

class ReplicationConsumeValidationException extends ReplicationException
{
    public function __construct($message)
    {
        parent::__construct('Replication Consume Exception: ' . $message);
    }
}
