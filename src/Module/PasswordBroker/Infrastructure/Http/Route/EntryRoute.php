<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Http\Route;

use App\Module\PasswordBroker\Infrastructure\Http\Controller\EntryController;
use App\Shared\Infrastructure\Http\Route\AbstractRouterRegistry;
use Inquisition\Core\Infrastructure\Http\Controller\RestControllerInterface;
use Inquisition\Core\Infrastructure\Http\Router\RouteGroupInterface;

final readonly class EntryRoute extends AbstractRouterRegistry
{
    public const string GROUP_NAME = 'entry';
    public const string PARAM_ENTRY_ID = 'entryId';

    private function __construct() {}

    #[\Override]
    public static function register(?RouteGroupInterface $parentRouteGroup = null): void
    {
        $routeGroup = self::inheritGroup(
            parentRouteGroup: $parentRouteGroup,
            newGroupName: self::GROUP_NAME,
        );

        $PARAM_ENTRY_GROUP_ID = EntryGroupRoute::PARAM_ENTRY_GROUP_ID;
        $PARAM_ENTRY_ID = self::PARAM_ENTRY_ID;
        $routeGroup
            ->prefix("/{{$PARAM_ENTRY_GROUP_ID}}/" . self::GROUP_NAME)
            ->get('', EntryController::class, RestControllerInterface::ACTION_INDEX)
            ->get("/{{$PARAM_ENTRY_ID}}", EntryController::class, RestControllerInterface::ACTION_SHOW)
            ->get('/search', EntryController::class, EntryController::ACTION_SEARCH)
            ->post('', EntryController::class, RestControllerInterface::ACTION_STORE)
            ->put("/{{$PARAM_ENTRY_ID}}", EntryController::class, RestControllerInterface::ACTION_UPDATE)
            ->delete("/{{$PARAM_ENTRY_ID}}", EntryController::class, RestControllerInterface::ACTION_DESTROY)
            ->patch("/{{$PARAM_ENTRY_ID}}/move", EntryController::class, EntryController::ACTION_MOVE)
        ;

        EntryFieldRoute::register($routeGroup);
    }
}
