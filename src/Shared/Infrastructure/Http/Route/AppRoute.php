<?php

namespace App\Shared\Infrastructure\Http\Route;

use Inquisition\Core\Infrastructure\Http\Middleware\CorsMiddleware;
use Inquisition\Core\Infrastructure\Http\Router\RouteGroupInterface;
use Inquisition\Core\Infrastructure\Http\Router\Router;

final readonly class AppRoute extends AbstractRouterRegistry
{

    public const string GROUP_NAME = 'app';

    private function __construct()
    {}

    public static function register(?RouteGroupInterface $parentRouteGroup = null): void
    {
        $router = Router::getInstance();

        $router->group(self::GROUP_NAME)
            ->middleware([
                new CorsMiddleware()
            ]);
    }
}