<?php

namespace App\Module\Identity\Infrastructure\Http\Middleware;

use App\Module\Identity\Application\User\Service\AuthApplicationService;
use Inquisition\Core\Infrastructure\Http\Middleware\MiddlewareInterface;
use Inquisition\Core\Infrastructure\Http\Request\RequestInterface;
use Inquisition\Core\Infrastructure\Http\Response\ResponseFactory;
use Inquisition\Core\Infrastructure\Http\Response\ResponseInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use JsonException;

final readonly class AuthMiddleware implements MiddlewareInterface
{
    private AuthApplicationService $authApplicationService;

    public function __construct()
    {
        $this->authApplicationService = AuthApplicationService::getInstance();
    }

    /**
     * @param RequestInterface $request
     * @param callable $next
     * @return ResponseInterface
     * @throws PersistenceException
     * @throws JsonException
     */
    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        if ($this->authApplicationService->authUser()) {
            return $next($request);
        }

        return ResponseFactory::unauthorized();
    }
}