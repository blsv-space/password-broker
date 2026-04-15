<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Http\Route;

use App\Module\PasswordBroker\Infrastructure\Http\Controller\EntryGroupController;
use App\Module\PasswordBroker\Infrastructure\Http\Middleware\EntryGroupModeratorOrAboveMiddleware;
use App\Shared\Infrastructure\Http\Route\AbstractRouterRegistry;
use Inquisition\Core\Infrastructure\Http\Router\RouteGroupInterface;

final readonly class EntryGroupModeratorRoute extends AbstractRouterRegistry
{
    public const string GROUP_NAME = 'entryGroup._moderator';
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
            ->middleware(new EntryGroupModeratorOrAboveMiddleware())
            ->get("/{{$PARAM_ENTRY_GROUP_ID}}/users", EntryGroupController::class, EntryGroupController::ACTION_USERS_IN_GROUP)
        ;
    }
}
