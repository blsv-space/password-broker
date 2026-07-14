<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Http\Route;

use App\Module\PasswordBroker\Infrastructure\Http\Controller\EntryGroupController;
use App\Module\PasswordBroker\Infrastructure\Http\Middleware\EntryGroupMemberOrAboveMiddleware;
use App\Shared\Infrastructure\Http\Route\AbstractRouterRegistry;
use Inquisition\Core\Infrastructure\Http\Controller\RestControllerInterface;
use Inquisition\Core\Infrastructure\Http\Router\RouteGroupInterface;

final readonly class EntryGroupRoute extends AbstractRouterRegistry
{
    public const string GROUP_NAME = 'entryGroup';
    public const string PARAM_ENTRY_GROUP_ID = 'entryGroupId';

    private function __construct() {}

    #[\Override]
    public static function register(?RouteGroupInterface $parentRouteGroup = null): void
    {
        $routeGroup = self::inheritGroup(
            parentRouteGroup: $parentRouteGroup,
            newGroupName: self::GROUP_NAME,
        );

        $PARAM_ENTRY_GROUP_ID = self::PARAM_ENTRY_GROUP_ID;
        $routeGroup
            ->prefix('/' . self::GROUP_NAME)
            ->middleware(new EntryGroupMemberOrAboveMiddleware())
            ->get('', EntryGroupController::class, RestControllerInterface::ACTION_INDEX)
            ->get("/{{$PARAM_ENTRY_GROUP_ID}}", EntryGroupController::class, RestControllerInterface::ACTION_SHOW)
            ->get('/search', EntryGroupController::class, EntryGroupController::ACTION_SEARCH)
        ;

        EntryGroupAdminRoute::register($routeGroup);
        EntryGroupModeratorRoute::register($routeGroup);
        EntryRoute::register($routeGroup);
    }
}
