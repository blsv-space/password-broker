<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Http\Controller;

use App\Module\Identity\Application\User\Service\Exception\AuthException;
use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\Identity\Infrastructure\Http\Controller\UserController;
use App\Module\PasswordBroker\Application\EntryField\Service\Exception\EntryFieldException;
use App\Module\PasswordBroker\Application\EntryFieldHistory\DTO\EntryFieldHistoryResponse\DecryptedResponse;
use App\Module\PasswordBroker\Application\EntryFieldHistory\DTO\EntryFieldHistoryResponse\EncryptedValueEntryFieldHistoryResponse;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Provider\EntryFieldHistoryResponseProvider;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Service\EntryFieldHistoryApplicationService;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\AuthUserNotInEntryGroupException;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\AbstractEntryFieldHistory;
use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use App\Module\PasswordBroker\Infrastructure\EntryFieldHistory\Repository\EntryFieldHistoryRepository;
use App\Module\PasswordBroker\Infrastructure\Http\Route\EntryFieldRoute;
use App\Shared\Domain\Security\Encryption\Exception\DecryptionException;
use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;
use App\Shared\Infrastructure\Security\Exception\JwtTokenExpiredException;
use Inquisition\Core\Infrastructure\Http\Controller\AbstractRestController;
use Inquisition\Core\Infrastructure\Http\Controller\RestControllerInterface;
use Inquisition\Core\Infrastructure\Http\Request\RequestInterface;
use Inquisition\Core\Infrastructure\Http\Response\ResponseFactory;
use Inquisition\Core\Infrastructure\Http\Response\ResponseInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use JsonException;
use Override;

final readonly class EntryFieldHistoryController extends AbstractRestController implements RestControllerInterface
{
    public const string ACTION_DECRYPT = 'decrypt';
    public const string ACTION_ENCRYPTED = 'encrypted';
    public const string FIELD_QUERY = 'query';
    public const string FIELD_VALUE = 'value';

    private EntryFieldHistoryApplicationService $entryFieldHistoryApplicationService;
    private EntryFieldHistoryResponseProvider $entryFieldHistoryResponseProvider;

    public function __construct()
    {
        $this->entryFieldHistoryApplicationService = EntryFieldHistoryApplicationService::getInstance();
        $this->entryFieldHistoryResponseProvider = EntryFieldHistoryResponseProvider::getInstance();
    }

    /**
     * @throws JsonException
     * @throws PersistenceException
     */
    #[Override]
    public function index(RequestInterface $request, array $parameters): ResponseInterface
    {
        ['page' => $page, 'per_page' => $per_page] = $this->getPaginationParams($request);
        $filterParams = $this->getFilterParams(
            request: $request,
            allowedFilters: [EntryFieldHistoryRepository::FIELD_TITLE],
        );
        ['field' => $field, 'direction' => $direction] = $this->getSortParams(
            request: $request,
            allowedSortFields: [
                EntryFieldHistoryRepository::FIELD_TITLE,
                EntryFieldHistoryRepository::FIELD_TYPE,
                EntryFieldHistoryRepository::FIELD_CREATED_AT,
                EntryFieldHistoryRepository::FIELD_IS_DELETED,
            ],
            defaultSort: EntryFieldRepository::FIELD_CREATED_AT,
        );
        $offset = ($page - 1) * $per_page;

        $criteria = [
            new QueryCriteria(
                field: EntryFieldHistoryRepository::FIELD_ENTRY_FIELD_ID,
                value: $parameters[EntryFieldHistoryRepository::FIELD_ENTRY_FIELD_ID],
            ),
            ...$filterParams,
        ];

        $entryFieldHistories = $this->entryFieldHistoryApplicationService->getEntryFieldHistoriesBy(
            criteria: $criteria,
            orderBy: [$field => $direction],
            limit: $per_page,
            offset: $offset,
        );

        $normalizeData = array_map(
            fn(AbstractEntryFieldHistory $entryFieldHistory)
                => $this->entryFieldHistoryResponseProvider->response($entryFieldHistory)->getAsArray(),
            $entryFieldHistories,
        );
        $total = $this->entryFieldHistoryApplicationService->countEntryFieldHistoriesBy($criteria);

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
     */
    #[Override]
    public function show(RequestInterface $request, array $parameters): ResponseInterface
    {
        $entryFieldHistory = $this->entryFieldHistoryApplicationService->getEntryFieldHistoryByUuid($parameters[EntryFieldRoute::PARAM_ENTRY_FIELD_ID]);

        if (!$entryFieldHistory) {
            return ResponseFactory::notFound();
        }

        return $this->jsonResponse(
            $this->normalizeData(
                data: $entryFieldHistory,
                entityResponseClassName: $this->entryFieldHistoryResponseProvider->response($entryFieldHistory)::class,
            ),
        );
    }

    /**
     * @throws AuthException
     * @throws AuthUserNotInEntryGroupException
     * @throws JsonException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     * @throws EntryFieldException
     * @throws DecryptionException
     */
    public function decrypt(RequestInterface $request, array $parameters): ResponseInterface
    {
        $entryFieldHistory = $this->entryFieldHistoryApplicationService->getEntryFieldHistoryByUuid($parameters[EntryFieldRoute::PARAM_ENTRY_FIELD_ID]);

        if (!$entryFieldHistory) {
            return ResponseFactory::notFound();
        }

        $decryptedValue = $this->entryFieldHistoryApplicationService->decryptEntryFieldHistory(
            $entryFieldHistory,
            $request->getParameter(UserController::FIELD_MASTER_PASSWORD),
        );

        return $this->jsonResponse(
            new DecryptedResponse(
                entryFieldHistoryId: $entryFieldHistory->id->toRaw(),
                decryptedValue: $decryptedValue,
            )->getAsArray(),
        );
    }

    /**
     * @throws AuthException
     * @throws AuthUserNotInEntryGroupException
     * @throws JsonException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     * @throws EntryFieldException
     * @throws DecryptionException
     */
    public function encrypted(RequestInterface $request, array $parameters): ResponseInterface
    {
        $entryFieldHistory = $this->entryFieldHistoryApplicationService->getEntryFieldHistoryByUuid($parameters[EntryFieldRoute::PARAM_ENTRY_FIELD_ID]);

        if (!$entryFieldHistory) {
            return ResponseFactory::notFound();
        }

        return $this->jsonResponse(
            $this->normalizeData(
                data: $entryFieldHistory,
                entityResponseClassName: EncryptedValueEntryFieldHistoryResponse::class,
            ),
        );
    }

}
