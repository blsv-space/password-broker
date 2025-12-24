<?php

namespace Tests\Shared;

use App\Module\Identity\Domain\User\ValueObject\UserId;
use Faker\Factory;
use Faker\Generator;
use Inquisition\Core\Domain\Entity\BaseEntity;
use Inquisition\Core\Infrastructure\Persistence\DatabaseConnections;
use Inquisition\Core\Infrastructure\Persistence\DatabaseManagerFactory;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use InvalidArgumentException;
use RuntimeException;

abstract class AbstractFixture
{

    protected static ?Generator $faker = null;
    /**
     * @var string[]
     */
    protected static array $idPool = [];

    /**
     * @param array $attributes
     * @param bool $persist
     * @return mixed
     */
    public static abstract function create(array $attributes = [], bool $persist = false);

    /**
     * @param int $count
     * @param array $attributes
     * @param bool $persist
     * @return array
     */
    public static abstract function createMany(int $count, array $attributes = [], bool $persist = true): array;

    /**
     * @return string
     */
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
     * @param string|null $id
     * @return string
     */
    protected static function generateId(?string $id = null): string
    {
        if ($id !== null) {
            self::$idPool[] = $id;

            return $id;
        }

        $id = UserId::generate()->toRaw();
        self::$idPool[] = $id;

        return $id;
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