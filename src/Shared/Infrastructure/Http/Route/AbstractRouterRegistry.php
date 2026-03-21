<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Route;

use Inquisition\Core\Infrastructure\Http\Router\RouteGroupInterface;
use Inquisition\Core\Infrastructure\Http\Router\Router;
use Inquisition\Core\Infrastructure\Http\Router\RouterRegistryInterface;
use RuntimeException;

abstract readonly class AbstractRouterRegistry implements RouterRegistryInterface
{
    public static function inheritGroup(
        string|RouteGroupInterface $parentRouteGroup,
        string $newGroupName,
    ): RouteGroupInterface {
        $router = Router::getInstance();
        $parentGroupRouter = $parentRouteGroup instanceof RouteGroupInterface
            ? $parentRouteGroup
            : $router->getGroup($parentRouteGroup);
        if (!$parentGroupRouter) {
            throw new RuntimeException("$parentRouteGroup is not registered");
        }

        return $parentGroupRouter->group($newGroupName);
    }

    #[\Override]
    abstract public static function register(?RouteGroupInterface $parentRouteGroup): void;
}
