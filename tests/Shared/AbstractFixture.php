<?php

namespace Tests\Shared;

use App\Shared\Infrastructure\Security\UuidGenerator;
use Faker\Factory;
use Faker\Generator;
use Inquisition\Core\Domain\Entity\BaseEntity;
use Inquisition\Core\Infrastructure\Persistence\DatabaseConnections;
use Inquisition\Core\Infrastructure\Persistence\DatabaseManagerFactory;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use InvalidArgumentException;
use Random\RandomException;
use RuntimeException;

abstract class AbstractFixture
{

    protected const int MIN_ID = 1_000_000;
    protected const int MAX_ID = 1_000_000_000;

    protected static ?Generator $faker = null;
    /**
     * @var int[]
     */
    protected static array $idPool = [];

    public static abstract function create(array $attributes = [], bool $persist = false);

    public static abstract function createMany(int $count, array $attributes = [], bool $persist = true);

    public static function getTableName(): string
    {
        throw new RuntimeException('Method getTableName not implemented in ' . static::class);
    }

    /**
     * @return Generator
     */
    protected static function faker(): Generator
    {
        if (self::$faker === null) {
            self::$faker = Factory::create();
        }

        return self::$faker;
    }

    /**
     * @param string|null $uuid
     * @return string
     */
    protected static function generateId(?string $uuid = null): string
    {
        if (is_null($uuid)) {
            $uuid = static::getUuid();
        }
        self::$idPool[] = $uuid;

        return $uuid;
    }

    /**
     * @return void
     */
    protected static function register(): void
    {
        FixtureRegister::register(static::class);
    }

    /**
     * @return void
     */
    public static function reset(): void
    {
        self::$idPool = [];
    }

    /**
     * @return string[]
     */
    public static function getIds(): array
    {
        return self::$idPool;
    }

    /**
     * @return string
     */
    public static function getId(): string
    {
        if (count(self::$idPool) === 0) {
            static::create(persist: true);
        }

        return self::$idPool[array_rand(self::$idPool)];
    }

    /**
     * @return string
     */
    public static function getUuid(): string
    {
        return new UuidGenerator()->generate();
    }

    /**
     * @param BaseEntity $entity
     * @param string|null $connectionName
     * @return void
     * @throws PersistenceException
     */
    public static function persist(BaseEntity $entity, ?string $connectionName = null): void
    {
        $databaseConnections = DatabaseConnections::getInstance();
        $databaseConnection = $databaseConnections->connect($connectionName);

        $databaseManagerFactory = DatabaseManagerFactory::getInstance();
        $databaseManager = $databaseManagerFactory->getManager($databaseConnection);
        if (!$databaseManager->exists()) {
            $databaseManager->create();
        }

        $attributes = $entity->getAsArray();
        if (empty($attributes)) {
            throw new InvalidArgumentException('Entity has no attributes, nothing to persist');
        }
        $fields = array_keys($attributes);

        $connect = $databaseConnection->connect();
        $tableName = static::getTableName();
        $statement = $connect->prepare("
            INSERT INTO `$tableName`
                (`" . implode('`, `', $fields) . "`)
                VALUES (:" . implode(', :', $fields) . ");
        ");

        $statement->execute($attributes);
    }
}