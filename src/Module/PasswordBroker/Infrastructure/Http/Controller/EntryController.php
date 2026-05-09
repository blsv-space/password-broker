<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Http\Controller;

use App\Module\Identity\Application\User\Service\Exception\AuthException;
use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\Identity\Infrastructure\Http\Controller\UserController;
use App\Module\PasswordBroker\Application\Entry\DTO\EntryResponse;
use App\Module\PasswordBroker\Application\Entry\Service\EntryApplicationService;
use App\Module\PasswordBroker\Infrastructure\Entry\Repository\EntryRepository;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\Repository\EntryGroupRepository;
use App\Module\PasswordBroker\Infrastructure\Http\Route\EntryGroupRoute;
use App\Module\PasswordBroker\Infrastructure\Http\Route\EntryRoute;
use App\Shared\Application\Validation\Rule\ValidUuidRule;
use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;
use App\Shared\Infrastructure\Security\Exception\JwtTokenExpiredException;
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
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use JsonException;
use Throwable;

final readonly class EntryController extends AbstractRestController implements RestControllerInterface
{
    public const string ACTION_MOVE = 'move';
    public const string ACTION_SEARCH = 'search';
    public const string FIELD_QUERY = 'query';

    private EntryApplicationService $entryApplicationService;

    public function __construct()
    {
        $this->entryApplicationService = EntryApplicationService::getInstance();
    }

    /**
     * @throws JsonException
     * @throws PersistenceException
     */
    #[\Override]
    public function index(RequestInterface $request, array $parameters): ResponseInterface
    {
        ['page' => $page, 'per_page' => $per_page] = $this->getPaginationParams($request);
        $filterParams = $this->getFilterParams(
            request: $request,
            allowedFilters: [EntryRepository::FIELD_TITLE],
        );
        ['field' => $field, 'direction' => $direction] = $this->getSortParams(
            request: $request,
            allowedSortFields: [
                EntryRepository::FIELD_TITLE,
                EntryRepository::FIELD_CREATED_AT,
                EntryRepository::FIELD_UPDATED_AT,
                EntryRepository::FIELD_DELETED_AT,
            ],
            defaultSort: EntryRepository::FIELD_TITLE,
        );
        $offset = ($page - 1) * $per_page;

        $criteria = [
            new QueryCriteria(
                field: EntryRepository::FIELD_ENTRY_GROUP_ID,
                value: $parameters[EntryGroupRoute::PARAM_ENTRY_GROUP_ID],
            ),
            ...$filterParams,
        ];

        $entries = $this->entryApplicationService->getEntryBy(
            criteria: $criteria,
            orderBy: [$field => $direction],
            limit: $per_page,
            offset: $offset,
        );

        $normalizeData = $this->normalizeData(
            data: $entries,
            entityResponseClassName: EntryResponse::class,
        );
        $total = $this->entryApplicationService->countEntryBy($criteria);

        return $this->jsonPaginatedResponse(
            data: $normalizeData,
            total: $total,
            page: $page,
            perPage: $per_page,
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
            EntryRepository::FIELD_TITLE => [
                new NotEmptyRule(),
                new MaxLengthRule(255),
            ],
            EntryRepository::FIELD_ENTRY_GROUP_ID => [
                new NotEmptyRule(),
                new ValidUuidRule(),
            ],
        ])->validate($request);

        $this->entryApplicationService->createEntryFromPrimitivesSync(
            title: $request->getParameter(EntryRepository::FIELD_TITLE),
            entryGroupId: $request->getParameter(EntryRepository::FIELD_ENTRY_GROUP_ID),
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
                data: $this->entryApplicationService->getEntryByUuid($parameters[EntryRoute::PARAM_ENTRY_ID]),
                entityResponseClassName: EntryResponse::class,
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
            EntryRepository::FIELD_TITLE => [
                new NotEmptyRule(),
                new MaxLengthRule(255),
            ],
        ])->validate($request);

        $this->entryApplicationService->renameEntrySync(
            uuid: $parameters[EntryRoute::PARAM_ENTRY_ID],
            title: $request->getParameter(EntryRepository::FIELD_TITLE),
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
        $this->entryApplicationService->deleteEntrySync($parameters[EntryRoute::PARAM_ENTRY_ID]);

        return $this->jsonResponse([], HttpStatusCode::NO_CONTENT);
    }

    /**
     * @throws AuthException
     * @throws JsonException
     * @throws PersistenceException
     * @throws ValidationException
     * @throws RsaDomainServiceException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     */
    public function move(RequestInterface $request, array $parameters): ResponseInterface
    {
        new HttpRequestValidator()->addRules([
            'targetId' => [
                new ValidUuidRule(),
            ],
            UserController::FIELD_MASTER_PASSWORD => [
                new NotEmptyRule(),
                new StringRule(),
            ],
        ])->validate($request);

        $this->entryApplicationService->moveEntrySync(
            uuid: $parameters[EntryRoute::PARAM_ENTRY_ID],
            targetUuid: $request->getParameter('targetId'),
            authUserMasterPassword: $request->getParameter(UserController::FIELD_MASTER_PASSWORD),
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

        $entries = $this->entryApplicationService->search(
            query: $request->getParameter(self::FIELD_QUERY, ''),
            criteria: [
                new QueryCriteria(
                    field: EntryRepository::FIELD_ENTRY_GROUP_ID,
                    value: $parameters[EntryGroupRoute::PARAM_ENTRY_GROUP_ID],
                ),
            ],
            orderBy: [EntryGroupRepository::FIELD_NAME => AbstractRepository::ORDER_ASC],
            limit: 10,
        );

        return $this->jsonResponse($this->normalizeData(
            data: $entries,
            entityResponseClassName: EntryResponse::class,
        ));
    }

}
