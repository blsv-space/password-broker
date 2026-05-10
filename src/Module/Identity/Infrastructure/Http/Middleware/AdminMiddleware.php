<?php

declare(strict_types=1);

namespace App\Module\Identity\Infrastructure\Http\Middleware;

use App\Module\Identity\Application\User\Service\AuthApplicationService;
use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;
use App\Shared\Infrastructure\Security\Exception\JwtTokenExpiredException;
use Inquisition\Core\Infrastructure\Http\Middleware\MiddlewareInterface;
use Inquisition\Core\Infrastructure\Http\Request\RequestInterface;
use Inquisition\Core\Infrastructure\Http\Response\ResponseFactory;
use Inquisition\Core\Infrastructure\Http\Response\ResponseInterface;
use Inquisition\Core\Infrastructure\Http\Router\RouteInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use JsonException;
use Override;

final readonly class AdminMiddleware implements MiddlewareInterface
{
    private AuthApplicationService $authApplicationService;

    public function __construct()
    {
        $this->authApplicationService = AuthApplicationService::getInstance();
    }

    /**
     * @throws JsonException
     * @throws PersistenceException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     */
    #[Override]
    public function process(RequestInterface $request, RouteInterface $route, callable $next): ResponseInterface
    {
        if ($this->authApplicationService->authUser()?->isAdmin->value ?? false) {
            return $next($request);
        }

        return ResponseFactory::forbidden("You don't have permission to access this resource.");
    }
}
