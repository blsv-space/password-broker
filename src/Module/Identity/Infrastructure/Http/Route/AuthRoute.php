<?php

namespace App\Module\Identity\Infrastructure\Http\Route;

use App\Module\Identity\Infrastructure\Http\Controller\AuthController;
use App\Shared\Infrastructure\Http\Route\AbstractRouterRegistry;
use Inquisition\Core\Application\Validation\HttpRequestValidator;
use Inquisition\Core\Infrastructure\Http\HttpMethod;
use Inquisition\Core\Infrastructure\Http\Router\RouteGroupInterface;

final readonly class AuthRoute extends AbstractRouterRegistry
{
    public const string GROUP_NAME = 'auth';

    private function __construct()
    {
    }

    public static function register(?RouteGroupInterface $parentRouteGroup = null): void
    {
        $routeGroup = self::inheritGroup(
            parentRouteGroup: $parentRouteGroup,
            newGroupName: self::GROUP_NAME
        );

        $routeGroup
            ->prefix('/auth')
            ->post('/login', AuthController::class, AuthController::ACTION_LOGIN)
            ->post('/logout', AuthController::class, AuthController::ACTION_LOGOUT)
            ->post('/refreshToken', AuthController::class, AuthController::ACTION_REFRESH_TOKEN);
    }
}