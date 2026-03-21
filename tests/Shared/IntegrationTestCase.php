<?php

declare(strict_types=1);

namespace Tests\Shared;

use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use PDO;

abstract class IntegrationTestCase extends AbstractTestCase
{
    protected PDO $connection;

    /**
     * @throws PersistenceException
     */
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->flushDatabase();
        $this->resetFixtures();
    }
}
