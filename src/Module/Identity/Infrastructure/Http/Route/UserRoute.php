<?php

namespace App\Module\Identity\Infrastructure\Http\Route;

use App\Module\Identity\Infrastructure\Http\Controller\UserController;
use App\Module\Identity\Infrastructure\Http\Middleware\AuthMiddleware;
use App\Shared\Infrastructure\Http\Route\AbstractRouterRegistry;
use Inquisition\Core\Infrastructure\Http\Controller\RestControllerInterface;
use Inquisition\Core\Infrastructure\Http\Router\RouteGroupInterface;

final readonly class UserRoute extends AbstractRouterRegistry
{
    public const string GROUP_NAME = 'user';

    private function __construct()
    {
    }

    /**
     * @param RouteGroupInterface|null $parentRouteGroup
     * @return void
     */
    public static function register(?RouteGroupInterface $parentRouteGroup = null): void
    {
        $routeGroup = self::inheritGroup(
            parentRouteGroup: $parentRouteGroup,
            newGroupName: self::GROUP_NAME
        );

        $routeGroup
            ->prefix('/user')
            ->middleware(new AuthMiddleware())
            ->get('', UserController::class, RestControllerInterface::ACTION_INDEX)
            ->post('', UserController::class, RestControllerInterface::ACTION_STORE)
            ->get('/{id}', UserController::class, RestControllerInterface::ACTION_SHOW)
            ->put('/{id}', UserController::class, RestControllerInterface::ACTION_UPDATE)
            ->delete('/{id}', UserController::class, RestControllerInterface::ACTION_DESTROY);
    }
}