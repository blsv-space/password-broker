<?php

declare(strict_types=1);

namespace Tests\Shared;

use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Foundation\Config\Config;
use Inquisition\Foundation\Kernel;
use Inquisition\Foundation\Singleton\SingletonRegistry;
use Inquisition\Foundation\Storage\StorageRegistry;

class BootTestHelper
{
    /**
     * @throws PersistenceException
     */
    public static function boot(): void
    {
        SingletonRegistry::getInstance()->resetAll();

        $kernel = Kernel::getInstance();
        $kernel->projectRoot = dirname(__DIR__, 2);
        $kernel->boot();

        $config = Config::getInstance();
        require $kernel->projectRoot . '/config/index.php';

        $envFile = $kernel->projectRoot . '/.env.test';
        if (file_exists($envFile)) {
            $config->loadEnvFromFile($envFile, true);
        }

        $config->loadFromEnvironment(prefix: 'APP_');

        $storage = StorageRegistry::getInstance()->storage('local');

        $storage->deleteDirectoryByPath('');

        require $kernel->projectRoot . '/config/routing.php';

        MockeryTestHelper::init();
    }
}
