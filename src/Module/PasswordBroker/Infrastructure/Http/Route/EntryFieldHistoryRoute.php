<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Http\Route;

use App\Module\PasswordBroker\Infrastructure\Http\Controller\EntryFieldHistoryController;
use App\Shared\Infrastructure\Http\Route\AbstractRouterRegistry;
use Inquisition\Core\Infrastructure\Http\Controller\RestControllerInterface;
use Inquisition\Core\Infrastructure\Http\Router\RouteGroupInterface;
use Override;

final readonly class EntryFieldHistoryRoute extends AbstractRouterRegistry
{
    public const string GROUP_NAME = 'entryFieldHistory';
    public const string PARAM_ENTRY_FIELD_HISTORY_ID = 'entryFieldHistoryId';

    private function __construct() {}

    #[Override]
    public static function register(?RouteGroupInterface $parentRouteGroup = null): void
    {
        $routeGroup = self::inheritGroup(
            parentRouteGroup: $parentRouteGroup,
            newGroupName: self::GROUP_NAME,
        );

        $PARAM_ENTRY_FIELD_ID = EntryFieldRoute::PARAM_ENTRY_FIELD_ID;
        $PARAM_ENTRY_FIELD_HISTORY_ID = self::PARAM_ENTRY_FIELD_HISTORY_ID;
        $routeGroup
            ->prefix("/{{$PARAM_ENTRY_FIELD_ID}}/" . self::GROUP_NAME)
            ->get('', EntryFieldHistoryController::class, RestControllerInterface::ACTION_INDEX)
            ->get("/{{$PARAM_ENTRY_FIELD_HISTORY_ID}}", EntryFieldHistoryController::class, RestControllerInterface::ACTION_SHOW)
            ->get("/{{$PARAM_ENTRY_FIELD_HISTORY_ID}}/encrypted", EntryFieldHistoryController::class, EntryFieldHistoryController::ACTION_ENCRYPTED)
            ->post("/{{$PARAM_ENTRY_FIELD_HISTORY_ID}}", EntryFieldHistoryController::class, EntryFieldHistoryController::ACTION_DECRYPT)
        ;
    }
}
