<?php

use App\Module\Identity\Infrastructure\Http\Route\IdentityRoute;
use App\Shared\Infrastructure\Http\Route\AppRoute;
use Inquisition\Core\Infrastructure\Http\Router\RouterRegistryInterface;

/**
 * @var RouterRegistryInterface $routes
 */
$routes = [
    AppRoute::class,

    IdentityRoute::class,
];

foreach ($routes as $route) {
    $route::register();
}