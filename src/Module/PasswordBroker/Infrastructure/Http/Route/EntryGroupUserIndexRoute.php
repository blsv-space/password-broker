<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Http\Route;

use App\Module\Identity\Infrastructure\Http\Middleware\AdminMiddleware;
use App\Module\PasswordBroker\Infrastructure\Http\Controller\EntryGroupUserController;
use App\Shared\Infrastructure\Http\Route\AbstractRouterRegistry;
use Inquisition\Core\Infrastructure\Http\Controller\RestControllerInterface;
use Inquisition\Core\Infrastructure\Http\Router\RouteGroupInterface;

final readonly class EntryGroupUserIndexRoute extends AbstractRouterRegistry
{
    public const string GROUP_NAME = '_index';

    private function __construct() {}

    #[\Override]
    public static function register(?RouteGroupInterface $parentRouteGroup = null): void
    {
        $routeGroup = self::inheritGroup(
            parentRouteGroup: $parentRouteGroup,
            newGroupName: self::GROUP_NAME,
        );

        $routeGroup
            ->middleware(new AdminMiddleware())
            ->get('', EntryGroupUserController::class, RestControllerInterface::ACTION_INDEX);
    }
}
