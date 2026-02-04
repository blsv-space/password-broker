<?php

namespace App\Module\PasswordBroker\Infrastructure\Http\Route;

use App\Module\Identity\Infrastructure\Http\Controller\UserController;
use App\Module\Identity\Infrastructure\Http\Middleware\AuthMiddleware;
use App\Module\PasswordBroker\Infrastructure\Http\Controller\EntryGroupController;
use App\Shared\Infrastructure\Http\Route\AbstractRouterRegistry;
use Inquisition\Core\Infrastructure\Http\Controller\RestControllerInterface;
use Inquisition\Core\Infrastructure\Http\Router\RouteGroupInterface;

final readonly class EntryGroupRoute extends AbstractRouterRegistry
{
    public const string GROUP_NAME = 'entryGroup';

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
            ->prefix('/entryGroup')
            ->middleware(new AuthMiddleware())
            ->get('', EntryGroupController::class, RestControllerInterface::ACTION_INDEX)
            ->post('', EntryGroupController::class, RestControllerInterface::ACTION_STORE)
            ->get('/{id}', EntryGroupController::class, RestControllerInterface::ACTION_SHOW)
            ->put('/{id}', EntryGroupController::class, RestControllerInterface::ACTION_UPDATE)
            ->delete('/{id}', EntryGroupController::class, RestControllerInterface::ACTION_DESTROY)
            ->patch('/{id}/move', EntryGroupController::class, EntryGroupController::ACTION_MOVE);
    }
}