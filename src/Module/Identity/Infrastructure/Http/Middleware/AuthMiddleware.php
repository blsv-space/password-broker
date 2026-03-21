<?php

declare(strict_types=1);

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
     * @throws PersistenceException
     * @throws JsonException
     */
    #[\Override]
    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        if ($this->authApplicationService->authUser()) {
            return $next($request);
        }

        return ResponseFactory::unauthorized();
    }
}
