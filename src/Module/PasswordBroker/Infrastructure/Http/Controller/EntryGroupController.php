<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Http\Controller;

use App\Module\Identity\Application\User\Service\Exception\AuthException;
use App\Module\PasswordBroker\Application\EntryGroup\DTO\EntryGroupResponse;
use App\Module\PasswordBroker\Application\EntryGroup\DTO\EntryGroupTreeResponse;
use App\Module\PasswordBroker\Application\EntryGroup\Service\EntryGroupApplicationService;
use App\Module\PasswordBroker\Application\EntryGroupUser\DTO\EntryGroupUserResponse;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\EntryGroupUserApplicationService;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\Repository\EntryGroupRepository;
use App\Module\PasswordBroker\Infrastructure\EntryGroupUser\Repository\EntryGroupUserRepository;
use App\Module\PasswordBroker\Infrastructure\Http\Route\EntryGroupRoute;
use App\Shared\Application\Validation\Rule\ValidUuidRule;
use Inquisition\Core\Application\Validation\Exception\ValidationException;
use Inquisition\Core\Application\Validation\HttpRequestValidator;
use Inquisition\Core\Application\Validation\Rule\MaxLengthRule;
use Inquisition\Core\Application\Validation\Rule\NotEmptyRule;
use Inquisition\Core\Application\Validation\Rule\StringRule;
use Inquisition\Core\Infrastructure\Http\Controller\AbstractRestController;
use Inquisition\Core\Infrastructure\Http\Controller\RestControllerInterface;
use Inquisition\Core\Infrastructure\Http\HttpStatusCode;
use Inquisition\Core\Infrastructure\Http\Request\RequestInterface;
use Inquisition\Core\Infrastructure\Http\Response\ResponseInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\AbstractRepository;
use JsonException;
use Throwable;

final readonly class EntryGroupController extends AbstractRestController implements RestControllerInterface
{
    public const string ACTION_MOVE = 'move';
    public const string ACTION_SEARCH = 'search';
    public const string ACTION_USERS_IN_GROUP = 'usersInGroup';
    public const string FIELD_QUERY = 'query';
    public const string FIELD_TARGET_ENTRY_GROUP_ID = 'taegetEntryGroupId';

    private EntryGroupApplicationService $entryGroupApplicationService;
    private EntryGroupUserApplicationService $entryGroupUserApplicationService;

    public function __construct()
    {
        $this->entryGroupApplicationService = EntryGroupApplicationService::getInstance();
        $this->entryGroupUserApplicationService = EntryGroupUserApplicationService::getInstance();
    }

    /**
     * @throws AuthException
     * @throws PersistenceException
     * @throws JsonException
     */
    #[\Override]
    public function index(RequestInterface $request, array $parameters): ResponseInterface
    {
        $trees = $this->entryGroupApplicationService->getEntryGroupsAsTree();

        return $this->jsonResponse(
            data: [
                EntryGroupTreeResponse::FIELD_TREES
                    => $this->normalizeData(
                        data: $trees,
                        entityResponseClassName: EntryGroupTreeResponse::class,
                    ),
            ],
        );
    }

    /**
     * @throws JsonException
     * @throws PersistenceException
     * @throws ValidationException
     * @throws Throwable
     */
    #[\Override]
    public function store(RequestInterface $request, array $parameters): ResponseInterface
    {
        new HttpRequestValidator()->addRules([
            EntryGroupRepository::FIELD_NAME => [
                new NotEmptyRule(),
                new MaxLengthRule(255),
            ],
            EntryGroupRepository::FIELD_PARENT_ENTRY_GROUP_ID => [
                new ValidUuidRule(),
            ],
        ])->validate($request);

        $this->entryGroupApplicationService->createEntryGroupFromPrimitivesSync(
            name: $request->getParameter(EntryGroupRepository::FIELD_NAME),
            parentEntryGroupId: $request->getParameter(EntryGroupRepository::FIELD_PARENT_ENTRY_GROUP_ID),
        );

        return $this->jsonResponse([], HttpStatusCode::CREATED);
    }

    /**
     * @throws JsonException
     * @throws PersistenceException
     */
    #[\Override]
    public function show(RequestInterface $request, array $parameters): ResponseInterface
    {
        return $this->jsonResponse(
            $this->normalizeData(
                data: $this->entryGroupApplicationService->getEntryGroupByUuid($parameters[EntryGroupRoute::PARAM_ENTRY_GROUP_ID]),
                entityResponseClassName: EntryGroupResponse::class,
            ),
        );
    }

    /**
     * @throws JsonException
     * @throws PersistenceException
     * @throws ValidationException
     */
    #[\Override]
    public function update(RequestInterface $request, array $parameters): ResponseInterface
    {
        new HttpRequestValidator()->addRules([
            EntryGroupRepository::FIELD_NAME => [
                new NotEmptyRule(),
                new MaxLengthRule(255),
            ],
        ])->validate($request);

        $this->entryGroupApplicationService->renameEntryGroupSync(
            uuid: $parameters[EntryGroupRoute::PARAM_ENTRY_GROUP_ID],
            name: $request->getParameter(EntryGroupRepository::FIELD_NAME),
        );

        return $this->jsonResponse([], HttpStatusCode::NO_CONTENT);
    }

    /**
     * @throws JsonException
     * @throws PersistenceException
     */
    #[\Override]
    public function destroy(RequestInterface $request, array $parameters): ResponseInterface
    {
        $this->entryGroupApplicationService->deleteEntryGroupSync($parameters[EntryGroupRoute::PARAM_ENTRY_GROUP_ID]);

        return $this->jsonResponse([], HttpStatusCode::NO_CONTENT);
    }

    /**
     * @throws JsonException
     * @throws PersistenceException
     * @throws ValidationException
     */
    public function move(RequestInterface $request, array $parameters): ResponseInterface
    {
        new HttpRequestValidator()->addRules([
            self::FIELD_TARGET_ENTRY_GROUP_ID => [
                new ValidUuidRule(),
            ],
        ])->validate($request);

        $this->entryGroupApplicationService->moveEntryGroupSync(
            uuid: $parameters[EntryGroupRoute::PARAM_ENTRY_GROUP_ID],
            targetUuid: $request->getParameter(self::FIELD_TARGET_ENTRY_GROUP_ID),
        );

        return $this->jsonResponse([], HttpStatusCode::NO_CONTENT);
    }

    /**
     * @throws JsonException
     * @throws PersistenceException
     * @throws ValidationException
     */
    public function search(RequestInterface $request, array $parameters): ResponseInterface
    {
        new HttpRequestValidator()->addRules([
            self::FIELD_QUERY => [
                new StringRule(),
            ],
        ])->validate($request);

        $entryGroups = $this->entryGroupApplicationService->search(
            query: $request->getParameter(self::FIELD_QUERY, ''),
            orderBy: [EntryGroupRepository::FIELD_NAME => AbstractRepository::ORDER_ASC],
            limit: 10,
        );

        return $this->jsonResponse($this->normalizeData(
            data: $entryGroups,
            entityResponseClassName: EntryGroupResponse::class,
        ));
    }

    /**
     * @throws JsonException
     * @throws PersistenceException
     */
    public function usersInGroup(RequestInterface $request, array $parameters): ResponseInterface
    {
        ['page' => $page, 'per_page' => $per_page] = $this->getPaginationParams($request);
        $filterParams = $this->getFilterParams(
            request: $request,
            allowedFilters: [
                EntryGroupUserRepository::FIELD_USER_ID,
                EntryGroupUserRepository::FIELD_ROLE,
            ],
        );
        ['field' => $field, 'direction' => $direction] = $this->getSortParams($request);
        $offset = ($page - 1) * $per_page;

        $entryGroupUsers = $this->entryGroupUserApplicationService->getEntryGroupUsersBy(
            criteria: $filterParams,
            orderBy: [$field => $direction],
            limit: $per_page,
            offset: $offset,
        );

        $total = $this->entryGroupUserApplicationService->countEntryGroupUsersBy($filterParams);

        return $this->jsonPaginatedResponse(
            data: $this->normalizeData($entryGroupUsers, EntryGroupUserResponse::class),
            total: $total,
            page: $page,
            perPage: $per_page,
        );
    }
}
