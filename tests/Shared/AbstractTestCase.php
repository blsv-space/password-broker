<?php

namespace Tests\Shared;

use App\Module\Identity\Application\User\Service\AuthApplicationService;
use App\Module\Identity\Domain\User\Entity\User;
use Faker\Factory;
use Faker\Generator;
use Inquisition\Core\Infrastructure\Migration\MigrationDiscovery;
use Inquisition\Core\Infrastructure\Migration\MigrationRunner;
use Inquisition\Core\Infrastructure\Persistence\DatabaseConnections;
use Inquisition\Core\Infrastructure\Persistence\DatabaseManagerFactory;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Foundation\Config\Config;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;

abstract class AbstractTestCase extends TestCase
{
    protected Generator $faker;

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();
    }

    /**
     * @param $table
     * @param array $param
     * @param string|null $connectionName
     * @return void
     */
    protected function assertDatabaseHas($table, array $param = [], ?string $connectionName = null): void
    {
        $databaseHas = $this->databaseHas($table, $param, $connectionName);

        $this->assertTrue(
            $databaseHas,
            sprintf(
                'Failed asserting that table [%s] contains row with data: %s',
                $table,
                json_encode($param)
            )
        );
    }

    private function databaseHas($table, array $param = [], ?string $connectionName = null): bool
    {
        $databaseConnections = DatabaseConnections::getInstance();
        $databaseConnection = $databaseConnections->connect($connectionName);

        $databaseManagerFactory = DatabaseManagerFactory::getInstance();
        $databaseManager = $databaseManagerFactory->getManager($databaseConnection);
        if (!$databaseManager->exists()) {
            throw new RuntimeException('Database does not exist');
        }

        $where = '';
        if (count($param) > 0) {
            $where = ' WHERE ' . implode(' AND ', array_map(fn(string $field) => "`$field` = :$field", array_keys($param)));
        }

        $statement = $databaseConnection->connect()->prepare("SELECT COUNT(*) FROM `$table` $where");
        $statement->execute($param);
        $count = (int)$statement->fetchColumn();

        return $count > 0;
    }

    /**
     * @param $table
     * @param array $param
     * @param string|null $connectionName
     * @return void
     */
    protected function assertDatabaseMissing($table, array $param = [], ?string $connectionName = null): void
    {
        $databaseHas = $this->databaseHas($table, $param, $connectionName);

        $this->assertTrue(
            !$databaseHas,
            sprintf(
                'Failed asserting that table [%s] does not contain row with data: %s',
                $table,
                json_encode($param)
            )
        );
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function flushDatabase(): void
    {
        $databaseConnections = DatabaseConnections::getInstance();

        $connections = Config::getInstance()->getByPath('database.connections', []);

        foreach ($connections as $name => $connection) {
            if (!is_string($name)) {
                continue;
            }
            $connection = $databaseConnections->connect($name);
            DatabaseManagerFactory::getInstance()->getManager($connection)->reset();
        }

        $migrationRunner = MigrationRunner::getInstance();
        $migrations = MigrationDiscovery::getInstance()->discover();
        foreach ($migrations as $migration) {
            $migrationRunner->registerMigration(new $migration());
        }
        $migrationRunner->runUp(
            silent: true,
        );
    }

    /**
     * @return void
     */
    public function resetFixtures(): void
    {
        FixtureRegister::reset();
    }

    /**
     * @param User $user
     * @return void
     * @throws ReflectionException
     */
    public function actAs(User $user): void
    {
        $authApplicationService = AuthApplicationService::getInstance();
        new ReflectionProperty($authApplicationService, 'authUser')->setValue($authApplicationService, $user);
    }

}