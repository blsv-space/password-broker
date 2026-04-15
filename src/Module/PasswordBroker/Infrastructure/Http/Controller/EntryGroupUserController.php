<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Http\Controller;

use App\Module\Identity\Application\User\Service\Exception\AuthException;
use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\Identity\Infrastructure\Http\Controller\UserController;
use App\Module\PasswordBroker\Application\EntryGroupUser\DTO\EntryGroupUserResponse;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\EntryGroupUserApplicationService;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\AuthUserHasNoRights;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\AuthUserNotInEntryGroupException;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\EntryGroupUserNotFoundException;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\TargetGroupNotFoundException;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\TargetUserNotFoundException;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\TargetUserNotInEntryGroupException;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Enum\RoleEnum;
use App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject\EntryGroupUserId;
use App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject\Role;
use App\Module\PasswordBroker\Infrastructure\EntryGroupUser\Repository\EntryGroupUserRepository;
use App\Module\PasswordBroker\Infrastructure\Http\Route\EntryGroupUserRoute;
use App\Shared\Application\Validation\Rule\ValidUuidRule;
use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;
use App\Shared\Infrastructure\Security\Exception\JwtTokenExpiredException;
use Inquisition\Core\Application\Validation\Exception\ValidationException;
use Inquisition\Core\Application\Validation\HttpRequestValidator;
use Inquisition\Core\Application\Validation\Rule\InRule;
use Inquisition\Core\Application\Validation\Rule\NotEmptyRule;
use Inquisition\Core\Infrastructure\Http\Controller\AbstractRestController;
use Inquisition\Core\Infrastructure\Http\Controller\RestControllerInterface;
use Inquisition\Core\Infrastructure\Http\HttpStatusCode;
use Inquisition\Core\Infrastructure\Http\Request\RequestInterface;
use Inquisition\Core\Infrastructure\Http\Response\ResponseInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use JsonException;
use Override;

final readonly class EntryGroupUserController extends AbstractRestController implements RestControllerInterface
{
    private EntryGroupUserApplicationService $entryGroupUserApplicationService;

    public function __construct(EntryGroupUserApplicationService $entryGroupUserApplicationService)
    {
        $this->entryGroupUserApplicationService = $entryGroupUserApplicationService;
    }

    /**
     * @throws PersistenceException
     * @throws JsonException
     */
    #[\Override]
    public function index(RequestInterface $request, array $parameters): ResponseInterface
    {
        ['page' => $page, 'per_page' => $per_page] = $this->getPaginationParams($request);
        $filterParams = $this->getFilterParams(
            request: $request,
            allowedFilters: [
                EntryGroupUserRepository::FIELD_USER_ID,
                EntryGroupUserRepository::FIELD_ENTRY_GROUP_ID,
                EntryGroupUserRepository::FIELD_ROLE,
            ],
        );
        ['field' => $field, 'direction' => $direction] = $this->getSortParams($request);
        $offset = ($page - 1) * $per_page;

        $users = $this->entryGroupUserApplicationService->getEntryGroupUsersBy(
            criteria: $filterParams,
            orderBy: [$field => $direction],
            limit: $per_page,
            offset: $offset,
        );

        $normalizeData = $this->normalizeData(
            data: $users,
            entityResponseClassName: EntryGroupUserResponse::class,
        );
        $total = $this->entryGroupUserApplicationService->countEntryGroupUsersBy($filterParams);

        return $this->jsonPaginatedResponse(
            data: $normalizeData,
            total: $total,
            page: $page,
            perPage: $per_page,
        );
    }

    /**
     * @throws PersistenceException
     * @throws ValidationException
     * @throws AuthException
     * @throws RsaDomainServiceException
     * @throws AuthUserHasNoRights
     * @throws AuthUserNotInEntryGroupException
     * @throws TargetGroupNotFoundException
     * @throws TargetUserNotFoundException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws JsonException
     */
    #[Override]
    public function store(RequestInterface $request, array $parameters): ResponseInterface
    {
        new HttpRequestValidator()->addRules([
            EntryGroupUserRepository::FIELD_USER_ID => [
                new ValidUuidRule(),
            ],
            EntryGroupUserRepository::FIELD_ENTRY_GROUP_ID => [
                new ValidUuidRule(),
            ],
            EntryGroupUserRepository::FIELD_ROLE => [
                new InRule(
                    allowedValues: RoleEnum::toArray(),
                    strict: true,
                ),
            ],
            UserController::FIELD_MASTER_PASSWORD => [
                new NotEmptyRule(),
            ],
        ])->validate($request);

        $entryGroupUser = $this->entryGroupUserApplicationService->addUserToGroup(
            targetUserId: UserId::fromRaw($request->getParameter(EntryGroupUserRepository::FIELD_USER_ID)),
            entryGroupId: EntryGroupId::fromRaw($request->getParameter(EntryGroupUserRepository::FIELD_ENTRY_GROUP_ID)),
            role: Role::fromRaw($request->getParameter(EntryGroupUserRepository::FIELD_ROLE)),
            authUserMasterPassword: $request->getParameter(UserController::FIELD_MASTER_PASSWORD),
        );

        return $this->jsonResponse(
            $this->normalizeData(
                $entryGroupUser,
                EntryGroupUserResponse::class,
            ),
            HttpStatusCode::CREATED,
        );
    }

    /**
     * @throws JsonException
     * @throws PersistenceException
     */
    #[Override]
    public function show(RequestInterface $request, array $parameters): ResponseInterface
    {
        return $this->jsonResponse(
            $this->normalizeData(
                data: $this->entryGroupUserApplicationService->getEntryGroupUserById(
                    $parameters[EntryGroupUserRoute::PARAM_ENTRY_GROUP_USER_ID],
                ),
                entityResponseClassName: EntryGroupUserResponse::class,
            ),
        );
    }

    /**
     * @throws AuthException
     * @throws AuthUserHasNoRights
     * @throws AuthUserNotInEntryGroupException
     * @throws JsonException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws PersistenceException
     * @throws TargetGroupNotFoundException
     * @throws TargetUserNotFoundException
     * @throws ValidationException
     * @throws EntryGroupUserNotFoundException
     * @throws TargetUserNotInEntryGroupException
     */
    #[Override]
    public function update(RequestInterface $request, array $parameters): ResponseInterface
    {
        new HttpRequestValidator()->addRules([
            EntryGroupUserRepository::FIELD_ROLE => [
                new InRule(
                    allowedValues: RoleEnum::toArray(),
                    strict: true,
                ),
            ],
        ])->validate($request);

        $this->entryGroupUserApplicationService->changeUserRoleByEntryGroupUserId(
            entryGroupUserId: EntryGroupUserId::fromRaw($parameters[EntryGroupUserRoute::PARAM_ENTRY_GROUP_USER_ID]),
            role: Role::fromRaw($request->getParameter(EntryGroupUserRepository::FIELD_ROLE)),
        );

        return $this->jsonResponse([], HttpStatusCode::NO_CONTENT);
    }

    /**
     * @throws JsonException
     * @throws PersistenceException
     */
    #[Override]
    public function destroy(RequestInterface $request, array $parameters): ResponseInterface
    {
        $this->entryGroupUserApplicationService->deleteEntryGroupSync(
            EntryGroupUserId::fromRaw($parameters[EntryGroupUserRoute::PARAM_ENTRY_GROUP_USER_ID]),
        );

        return $this->jsonResponse([], HttpStatusCode::NO_CONTENT);
    }

}
