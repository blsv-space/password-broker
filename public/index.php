<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Inquisition\Core\Infrastructure\Http\HttpStatusCode;
use Inquisition\Core\Infrastructure\Http\Request\HttpRequest as HttpRequestInquisition;
use Inquisition\Core\Infrastructure\Http\Response\HttpResponse as HttpResponseInquisition;
use Inquisition\Core\Infrastructure\Http\Router\Exception\RouteNotFoundException;
use Inquisition\Core\Infrastructure\Http\Router\RequestDispatcher;
use Inquisition\Foundation\Config\Config;
use Inquisition\Foundation\Kernel;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$kernel = Kernel::getInstance();
$kernel->projectRoot = dirname(__DIR__);
$kernel->boot();

$config = Config::getInstance();

require_once $kernel->projectRoot . '/config/index.php';

$envFile = $kernel->projectRoot . '/.env';
if (file_exists($envFile)) {
    $config->loadEnvFromFile($envFile, true);
}

$config->loadFromEnvironment(prefix: 'APP_');

require_once $kernel->projectRoot . '/config/routing.php';

try {
    RequestDispatcher::getInstance()->handle(HttpRequestInquisition::createFromGlobals())
        ->send();
} catch (RouteNotFoundException $e) {
    new HttpResponseInquisition()
        ->setStatusCode(HttpStatusCode::NOT_FOUND)
        ->send();
}
