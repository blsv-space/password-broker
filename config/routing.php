<?php

declare(strict_types=1);

use App\Module\Identity\Infrastructure\Http\Route\IdentityRoute;
use App\Module\PasswordBroker\Infrastructure\Http\Route\PasswordBrokerRoute;
use App\Shared\Infrastructure\Http\Route\AppRoute;
use Inquisition\Core\Infrastructure\Http\Router\RouterRegistryInterface;

/**
 * @var RouterRegistryInterface $routes
 */
$routes = [
    AppRoute::class,

    IdentityRoute::class,
    PasswordBrokerRoute::class,
];

foreach ($routes as $route) {
    $route::register();
}
