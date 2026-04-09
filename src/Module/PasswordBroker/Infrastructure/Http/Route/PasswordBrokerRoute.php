<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Http\Route;

use App\Module\Identity\Infrastructure\Http\Middleware\AuthMiddleware;
use App\Shared\Infrastructure\Http\Route\AbstractRouterRegistry;
use App\Shared\Infrastructure\Http\Route\AppRoute;
use Inquisition\Core\Infrastructure\Http\Router\RouteGroupInterface;

final readonly class PasswordBrokerRoute extends AbstractRouterRegistry
{
    public const string GROUP_NAME = 'passwordBroker';

    private function __construct() {}

    #[\Override]
    public static function register(?RouteGroupInterface $parentRouteGroup = null): void
    {
        $routeGroup = self::inheritGroup(
            parentRouteGroup: $parentRouteGroup ?? AppRoute::GROUP_NAME,
            newGroupName: self::GROUP_NAME,
        )->middleware(new AuthMiddleware())
            ->prefix('/passwordBroker');

        EntryGroupRoute::register($routeGroup);
        EntryGroupUserRoute::register($routeGroup);
    }

}
