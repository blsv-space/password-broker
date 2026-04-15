<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Http\Route;

use App\Module\PasswordBroker\Infrastructure\Http\Controller\EntryGroupUserController;
use App\Module\PasswordBroker\Infrastructure\Http\Middleware\EntryGroupMemberOrAboveMiddleware;
use App\Shared\Infrastructure\Http\Route\AbstractRouterRegistry;
use Inquisition\Core\Infrastructure\Http\Controller\RestControllerInterface;
use Inquisition\Core\Infrastructure\Http\Router\RouteGroupInterface;

final readonly class EntryGroupUserRoute extends AbstractRouterRegistry
{
    public const string GROUP_NAME = 'entryGroupUser';
    public const string PARAM_ENTRY_GROUP_USER_ID = 'entryGroupUserId';

    private function __construct() {}

    #[\Override]
    public static function register(?RouteGroupInterface $parentRouteGroup = null): void
    {
        $routeGroup = self::inheritGroup(
            parentRouteGroup: $parentRouteGroup,
            newGroupName: self::GROUP_NAME,
        );

        $PARAM_ENTRY_GROUP_USER_ID = self::PARAM_ENTRY_GROUP_USER_ID;
        $routeGroup
            ->prefix('/' . self::GROUP_NAME)
            ->middleware(new EntryGroupMemberOrAboveMiddleware())
            ->post('', EntryGroupUserController::class, RestControllerInterface::ACTION_STORE)
            ->get("/{{$PARAM_ENTRY_GROUP_USER_ID}}", EntryGroupUserController::class, RestControllerInterface::ACTION_SHOW)
            ->put("/{{$PARAM_ENTRY_GROUP_USER_ID}}", EntryGroupUserController::class, RestControllerInterface::ACTION_UPDATE)
            ->delete("/{{$PARAM_ENTRY_GROUP_USER_ID}}", EntryGroupUserController::class, RestControllerInterface::ACTION_DESTROY)
        ;

        EntryGroupUserIndexRoute::register($routeGroup);
    }
}
