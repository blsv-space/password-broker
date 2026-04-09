<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Http\Route;

use App\Module\PasswordBroker\Infrastructure\Http\Controller\EntryGroupController;
use App\Shared\Infrastructure\Http\Route\AbstractRouterRegistry;
use Inquisition\Core\Infrastructure\Http\Controller\RestControllerInterface;
use Inquisition\Core\Infrastructure\Http\Router\RouteGroupInterface;

final readonly class EntryGroupRoute extends AbstractRouterRegistry
{
    public const string GROUP_NAME = 'entryGroup';

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
            ->get('', EntryGroupController::class, RestControllerInterface::ACTION_INDEX)
            ->post('', EntryGroupController::class, RestControllerInterface::ACTION_STORE)
            ->get('/{id}', EntryGroupController::class, RestControllerInterface::ACTION_SHOW)
            ->put('/{id}', EntryGroupController::class, RestControllerInterface::ACTION_UPDATE)
            ->delete('/{id}', EntryGroupController::class, RestControllerInterface::ACTION_DESTROY)
            ->patch('/{id}/move', EntryGroupController::class, EntryGroupController::ACTION_MOVE)
            ->get('/search', EntryGroupController::class, EntryGroupController::ACTION_SEARCH)
        ;
    }
}
