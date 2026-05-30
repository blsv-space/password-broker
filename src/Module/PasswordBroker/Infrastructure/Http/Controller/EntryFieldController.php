<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Http\Controller;

use App\Module\Identity\Application\User\Service\Exception\AuthException;
use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\Identity\Infrastructure\Http\Controller\UserController;
use App\Module\PasswordBroker\Application\EntryField\Provider\EntryFieldResponseProvider;
use App\Module\PasswordBroker\Application\EntryField\Service\EntryFieldApplicationService;
use App\Module\PasswordBroker\Application\EntryField\Service\Exception\EntryFieldNotFountException;
use App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception\AuthUserNotInEntryGroupException;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTotpHashAlgorithmEnum;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use App\Module\PasswordBroker\Infrastructure\Entry\Repository\EntryRepository;
use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use App\Module\PasswordBroker\Infrastructure\Http\Route\EntryFieldRoute;
use App\Module\PasswordBroker\Infrastructure\Http\Route\EntryRoute;
use App\Shared\Domain\Security\Encryption\Exception\EncryptionException;
use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;
use App\Shared\Infrastructure\Security\Exception\JwtTokenExpiredException;
use Inquisition\Core\Application\Validation\Exception\ValidationException;
use Inquisition\Core\Application\Validation\HttpRequestValidator;
use Inquisition\Core\Application\Validation\Rule\InRule;
use Inquisition\Core\Application\Validation\Rule\MaxLengthRule;
use Inquisition\Core\Application\Validation\Rule\MinValueRule;
use Inquisition\Core\Application\Validation\Rule\NotEmptyRule;
use Inquisition\Core\Infrastructure\Http\Controller\AbstractRestController;
use Inquisition\Core\Infrastructure\Http\Controller\RestControllerInterface;
use Inquisition\Core\Infrastructure\Http\HttpStatusCode;
use Inquisition\Core\Infrastructure\Http\Request\RequestInterface;
use Inquisition\Core\Infrastructure\Http\Response\ResponseInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use JsonException;
use Override;
use Throwable;

final readonly class EntryFieldController extends AbstractRestController implements RestControllerInterface
{
    public const string ACTION_SEARCH = 'search';
    public const string FIELD_QUERY = 'query';
    public const string FIELD_VALUE = 'value';

    private EntryFieldApplicationService $entryFieldApplicationService;
    private EntryFieldResponseProvider $entryFieldResponseProvider;

    public function __construct()
    {
        $this->entryFieldApplicationService = EntryFieldApplicationService::getInstance();
        $this->entryFieldResponseProvider = EntryFieldResponseProvider::getInstance();
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
            allowedFilters: [EntryFieldRepository::FIELD_TITLE],
        );
        ['field' => $field, 'direction' => $direction] = $this->getSortParams(
            request: $request,
            allowedSortFields: [
                EntryFieldRepository::FIELD_TITLE,
                EntryFieldRepository::FIELD_TYPE,
                EntryFieldRepository::FIELD_CREATED_AT,
                EntryFieldRepository::FIELD_UPDATED_AT,
                EntryFieldRepository::FIELD_DELETED_AT,
            ],
            defaultSort: EntryFieldRepository::FIELD_TITLE,
        );
        $offset = ($page - 1) * $per_page;

        $criteria = [
            new QueryCriteria(
                field: EntryFieldRepository::FIELD_ENTRY_ID,
                value: $parameters[EntryFieldRepository::FIELD_ENTRY_ID],
            ),
            ...$filterParams,
        ];

        $entryFields = $this->entryFieldApplicationService->getEntryFieldsBy(
            criteria: $criteria,
            orderBy: [$field => $direction],
            limit: $per_page,
            offset: $offset,
        );

        $normalizeData = array_map(
            fn(AbstractEntryField $entryField)
                => $this->normalizeData(
                    data: $entryField,
                    entityResponseClassName: $this->entryFieldResponseProvider->response($entryField)::class,
                ),
            $entryFields,
        );
        $total = $this->entryFieldApplicationService->countEntryFieldsBy($criteria);

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
    #[Override]
    public function store(RequestInterface $request, array $parameters): ResponseInterface
    {
        $this->validateEntryField($request);

        $this->entryFieldApplicationService->createEntryFieldFromPrimitivesSync(
            entryId: $parameters[EntryRoute::PARAM_ENTRY_ID],
            type: $request->getParameter(EntryFieldRepository::FIELD_TYPE),
            title: $request->getParameter(EntryFieldRepository::FIELD_TITLE),
            value: $request->getParameter(self::FIELD_VALUE),
            masterPassword: $request->getParameter(UserController::FIELD_MASTER_PASSWORD),
            fileName: $request->getParameter(EntryFieldRepository::FIELD_FILE_NAME),
            fileMime: $request->getParameter(EntryFieldRepository::FIELD_FILE_MIME),
            fileSize: $request->getParameter(EntryFieldRepository::FIELD_FILE_SIZE),
            login: $request->getParameter(EntryFieldRepository::FIELD_LOGIN),
            totpHashAlgorithm: $request->getParameter(EntryFieldRepository::FIELD_TOTP_HASH_ALGORITHM),
            totpTimeout: $request->getParameter(EntryFieldRepository::FIELD_TOTP_TIMEOUT),
        );

        return $this->jsonResponse([], HttpStatusCode::CREATED);
    }

    /**
     * @throws JsonException
     * @throws PersistenceException
     */
    #[Override]
    public function show(RequestInterface $request, array $parameters): ResponseInterface
    {
        $entryField = $this->entryFieldApplicationService->getEntryFieldByUuid($parameters[EntryFieldRoute::PARAM_ENTRY_FIELD_ID]);

        return $this->jsonResponse(
            $this->normalizeData(
                data: $entryField,
                entityResponseClassName: $this->entryFieldResponseProvider->response($entryField)::class,
            ),
        );
    }

    /**
     * @throws AuthException
     * @throws JsonException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     * @throws ValidationException
     * @throws EntryFieldNotFountException
     * @throws AuthUserNotInEntryGroupException
     * @throws EncryptionException
     */
    #[Override]
    public function update(RequestInterface $request, array $parameters): ResponseInterface
    {
        $this->validateEntryField($request);

        $this->entryFieldApplicationService->updateEntryFieldSync(
            id: $parameters[EntryFieldRoute::PARAM_ENTRY_FIELD_ID],
            title: $request->getParameter(EntryRepository::FIELD_TITLE),
            value: $request->getParameter(self::FIELD_VALUE),
            masterPassword: $request->getParameter(UserController::FIELD_MASTER_PASSWORD),
            fileName: $request->getParameter(EntryFieldRepository::FIELD_FILE_NAME),
            fileMime: $request->getParameter(EntryFieldRepository::FIELD_FILE_MIME),
            fileSize: $request->getParameter(EntryFieldRepository::FIELD_FILE_SIZE),
            login: $request->getParameter(EntryFieldRepository::FIELD_LOGIN),
            totpHashAlgorithm: $request->getParameter(EntryFieldRepository::FIELD_TOTP_HASH_ALGORITHM),
            totpTimeout: $request->getParameter(EntryFieldRepository::FIELD_TOTP_TIMEOUT),
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
        $this->entryFieldApplicationService->deleteEntryFieldSync($parameters[EntryFieldRoute::PARAM_ENTRY_FIELD_ID]);

        return $this->jsonResponse([], HttpStatusCode::NO_CONTENT);
    }

    /**
     * @throws ValidationException
     */
    private function validateEntryField(RequestInterface $request): void
    {
        new HttpRequestValidator()->addRules([
            EntryFieldRepository::FIELD_TITLE => [
                new MaxLengthRule(255),
            ],
            EntryFieldRepository::FIELD_TYPE => [
                new NotEmptyRule(),
                new InRule(EntryFieldTypeEnum::toArray(), strict: true),
            ],
            UserController::FIELD_MASTER_PASSWORD => [
                new NotEmptyRule(),
            ],
            self::FIELD_VALUE => [
                new NotEmptyRule(),
            ],
            EntryFieldRepository::FIELD_FILE_NAME => [
                new MaxLengthRule(255),
            ],
            EntryFieldRepository::FIELD_FILE_MIME => [
                new MaxLengthRule(255),
            ],
            EntryFieldRepository::FIELD_FILE_SIZE => [
                new MinValueRule(0),
            ],
            EntryFieldRepository::FIELD_TOTP_HASH_ALGORITHM => [
                new InRule(EntryFieldTotpHashAlgorithmEnum::toArray(), strict: true),
            ],
            EntryFieldRepository::FIELD_TOTP_TIMEOUT => [
                new MinValueRule(0),
            ],
        ])->validate($request);
    }
}
