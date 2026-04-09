<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Http\Route;

use App\Module\PasswordBroker\Infrastructure\Http\Controller\EntryGroupController;
use App\Module\PasswordBroker\Infrastructure\Http\Controller\EntryGroupUserController;
use App\Shared\Infrastructure\Http\Route\AbstractRouterRegistry;
use Inquisition\Core\Infrastructure\Http\Controller\RestControllerInterface;
use Inquisition\Core\Infrastructure\Http\Router\RouteGroupInterface;

final readonly class EntryGroupUserRoute extends AbstractRouterRegistry
{
    public const string GROUP_NAME = 'entryGroupUser';

    private function __construct() {}

    #[\Override]
    public static function register(?RouteGroupInterface $parentRouteGroup = null): void
    {
        $routeGroup = self::inheritGroup(
            parentRouteGroup: $parentRouteGroup,
            newGroupName: self::GROUP_NAME,
        );

        $routeGroup
            ->prefix('/' . self::GROUP_NAME)
            ->post('', EntryGroupUserController::class, RestControllerInterface::ACTION_STORE)
            ->get('/{id}', EntryGroupUserController::class, RestControllerInterface::ACTION_SHOW)
            ->put('/{id}', EntryGroupUserController::class, RestControllerInterface::ACTION_UPDATE)
            ->delete('/{id}', EntryGroupUserController::class, RestControllerInterface::ACTION_DESTROY)
        ;
    }
}
