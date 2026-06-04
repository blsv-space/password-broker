<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Http\Route;

use App\Module\PasswordBroker\Infrastructure\Http\Controller\EntryFieldController;
use App\Shared\Infrastructure\Http\Route\AbstractRouterRegistry;
use Inquisition\Core\Infrastructure\Http\Controller\RestControllerInterface;
use Inquisition\Core\Infrastructure\Http\Router\RouteGroupInterface;
use Override;

final readonly class EntryFieldRoute extends AbstractRouterRegistry
{
    public const string GROUP_NAME = 'entryField';
    public const string PARAM_ENTRY_FIELD_ID = 'entryFieldId';

    private function __construct() {}

    #[Override]
    public static function register(?RouteGroupInterface $parentRouteGroup = null): void
    {
        $routeGroup = self::inheritGroup(
            parentRouteGroup: $parentRouteGroup,
            newGroupName: self::GROUP_NAME,
        );

        $PARAM_ENTRY_ID = EntryRoute::PARAM_ENTRY_ID;
        $PARAM_ENTRY_FIELD_ID = self::PARAM_ENTRY_FIELD_ID;
        $routeGroup
            ->prefix("/{{$PARAM_ENTRY_ID}}/" . self::GROUP_NAME)
            ->get('', EntryFieldController::class, RestControllerInterface::ACTION_INDEX)
            ->get("/{{$PARAM_ENTRY_FIELD_ID}}", EntryFieldController::class, RestControllerInterface::ACTION_SHOW)
            ->post('', EntryFieldController::class, RestControllerInterface::ACTION_STORE)
            ->put("/{{$PARAM_ENTRY_FIELD_ID}}", EntryFieldController::class, RestControllerInterface::ACTION_UPDATE)
            ->delete("/{{$PARAM_ENTRY_FIELD_ID}}", EntryFieldController::class, RestControllerInterface::ACTION_DESTROY)
            ->post("/{{$PARAM_ENTRY_FIELD_ID}}", EntryFieldController::class, EntryFieldController::ACTION_DECRYPT)
        ;
    }
}
