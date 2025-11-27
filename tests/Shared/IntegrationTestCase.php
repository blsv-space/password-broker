<?php

namespace Tests\Shared;

use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use PDO;

abstract class IntegrationTestCase extends AbstractTestCase
{
    protected PDO $connection;

    /**
     * @return void
     * @throws PersistenceException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->flushDatabase();
        $this->resetFixtures();
    }
}