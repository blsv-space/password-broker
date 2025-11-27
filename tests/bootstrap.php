<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Inquisition\Foundation\Config\Config;
use Inquisition\Foundation\Kernel;
$kernel = Kernel::getInstance();
$kernel->projectRoot = dirname(__DIR__);
$kernel->boot();


$config = Config::getInstance();
require_once $kernel->projectRoot . '/config/index.php';

$envFile = $kernel->projectRoot . '/.env.test';
if (file_exists($envFile)) {
    $config->loadEnvFromFile($envFile, true);
}

$config->loadFromEnvironment(prefix: 'APP_');
require_once $kernel->projectRoot . '/config/routing.php';
