<?php

namespace App\Module\Identity\Infrastructure\Http\Route;

use App\Module\Identity\Infrastructure\Http\Middleware\AuthMiddleware;
use App\Shared\Infrastructure\Http\Route\AbstractRouterRegistry;
use App\Shared\Infrastructure\Http\Route\AppRoute;
use Inquisition\Core\Infrastructure\Http\Router\RouteGroupInterface;

final readonly class IdentityRoute extends AbstractRouterRegistry
{
    public const string GROUP_NAME = 'identity';

    private function __construct()
    {
    }

    public static function register(?RouteGroupInterface $parentRouteGroup = null): void
    {
        $routeGroup = self::inheritGroup(
            parentRouteGroup: $parentRouteGroup ?? AppRoute::GROUP_NAME,
            newGroupName: self::GROUP_NAME
        );

        $routeGroup->group(self::GROUP_NAME)
            ->middleware(new AuthMiddleware())
            ->prefix('/identity');

        UserRoute::register($routeGroup);
        AuthRoute::register($routeGroup);
    }

}