<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Http\Middleware;

use App\Module\Identity\Application\User\Service\AuthApplicationService;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\EntryGroupUserApplicationService;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Enum\RoleEnum;
use App\Module\PasswordBroker\Infrastructure\EntryGroupUser\Repository\EntryGroupUserRepository;
use App\Module\PasswordBroker\Infrastructure\Http\Route\EntryGroupRoute;
use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;
use App\Shared\Infrastructure\Security\Exception\JwtTokenExpiredException;
use Inquisition\Core\Infrastructure\Http\Middleware\MiddlewareInterface;
use Inquisition\Core\Infrastructure\Http\Request\RequestInterface;
use Inquisition\Core\Infrastructure\Http\Response\ResponseFactory;
use Inquisition\Core\Infrastructure\Http\Response\ResponseInterface;
use Inquisition\Core\Infrastructure\Http\Router\RouteInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use JsonException;

final readonly class EntryGroupAdminMiddleware implements MiddlewareInterface
{
    private AuthApplicationService $authApplicationService;
    private EntryGroupUserApplicationService $entryGroupUserApplicationService;

    public function __construct()
    {
        $this->authApplicationService = AuthApplicationService::getInstance();
        $this->entryGroupUserApplicationService = EntryGroupUserApplicationService::getInstance();
    }

    /**
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws PersistenceException
     * @throws JsonException
     */
    #[\Override]
    public function process(RequestInterface $request, RouteInterface $route, callable $next): ResponseInterface
    {
        $user = $this->authApplicationService->authUser();
        if (!$user) {
            return ResponseFactory::unauthorized();
        }
        $entryGroupId = $route->getParameters()[EntryGroupRoute::PARAM_ENTRY_GROUP_ID] ?? null;

        if (!$entryGroupId) {
            return $next($request);
        }

        $hasAccess = $this->entryGroupUserApplicationService->countEntryGroupUsersBy([
            new QueryCriteria(
                field: EntryGroupUserRepository::FIELD_USER_ID,
                value: $user->getId()->toRaw(),
            ),
            new QueryCriteria(
                field: EntryGroupUserRepository::FIELD_ROLE,
                value: RoleEnum::ADMIN->value,
            ),
            new QueryCriteria(
                field: EntryGroupUserRepository::FIELD_ENTRY_GROUP_ID,
                value: $entryGroupId,
            ),
        ]);

        if ($hasAccess === 0) {
            return ResponseFactory::forbidden("You don't have Moderator access to this entry group.");
        }

        return $next($request);
    }
}
