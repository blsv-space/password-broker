<?php

namespace App\Module\Identity\Infrastructure\Http\Controller;

use App\Module\Identity\Application\User\DTO\UserResponse;
use App\Module\Identity\Application\User\Service\UserApplicationService;
use App\Module\Identity\Infrastructure\User\Repository\UserRepository;
use Inquisition\Core\Application\Validation\HttpRequestValidator;
use Inquisition\Core\Application\Validation\Rule\MaxLengthRule;
use Inquisition\Core\Application\Validation\Rule\MinLengthRule;
use Inquisition\Core\Application\Validation\Rule\NotEmptyRule;
use Inquisition\Core\Infrastructure\Http\Controller\AbstractRestController;
use Inquisition\Core\Infrastructure\Http\Controller\RestControllerInterface;
use Inquisition\Core\Infrastructure\Http\HttpStatusCode;
use Inquisition\Core\Infrastructure\Http\Request\RequestInterface;
use Inquisition\Core\Infrastructure\Http\Response\ResponseInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Foundation\Config\Config;
use JsonException;
use Throwable;

final readonly class UserController extends AbstractRestController
    implements RestControllerInterface
{
    public const string FIELD_PASSWORD = 'password';
    public const string FIELD_MASTER_PASSWORD = 'masterPassword';
    public const string FIELD_EMAIL = UserRepository::FIELD_EMAIL;
    public const string FIELD_IS_ADMIN = UserRepository::FIELD_IS_ADMIN;

    private UserApplicationService $userApplicationService;

    public function __construct()
    {
        $this->userApplicationService = UserApplicationService::getInstance();
    }

    /**
     * @param RequestInterface $request
     * @param array $parameters
     * @return ResponseInterface
     * @throws PersistenceException
     * @throws JsonException
     */
    public function index(RequestInterface $request, array $parameters): ResponseInterface
    {
        ['page' => $page, 'per_page' => $per_page] = $this->getPaginationParams($request);
        $filterParams = $this->getFilterParams(
            request: $request,
            allowedFilters: [UserRepository::FIELD_USER_NAME],
        );
        ['field' => $field, 'direction' => $direction] = $this->getSortParams($request);
        $offset = ($page - 1) * $per_page;

        $users = $this->userApplicationService->getUsersBy(
            criteria: $filterParams,
            orderBy: [$field => $direction],
            limit: $per_page,
            offset: $offset,
        );

        $normalizeData = $this->normalizeData(
            data: $users,
            entityResponseClassName: UserResponse::class,
        );
        $total = $this->userApplicationService->countUsersBy($filterParams);

        return $this->jsonPaginatedResponse(
            data: $normalizeData,
            total: $total,
            page: $page,
            perPage: $per_page,
        );
    }

    /**
     * @param RequestInterface $request
     * @param array $parameters
     * @return ResponseInterface
     * @throws JsonException
     * @throws Throwable
     */
    public function store(RequestInterface $request, array $parameters): ResponseInterface
    {
        $httpRequestValidator = new HttpRequestValidator();
        $passwordMinLength = Config::getInstance()->getByPath('security.password_min_length', 8);
        $httpRequestValidator->addRules([
            UserRepository::FIELD_USER_NAME => [
                new NotEmptyRule(),
                new MinLengthRule(2),
                new MaxLengthRule(255)
            ],
            self::FIELD_PASSWORD => [
                new NotEmptyRule(),
                new MinLengthRule($passwordMinLength),
            ],
        ]);

        UserApplicationService::getInstance()->createUserSync(
            userName: $request->getParameter(UserRepository::FIELD_USER_NAME),
            password: $request->getParameter(self::FIELD_PASSWORD),
            email: $request->getParameter(self::FIELD_EMAIL),
            masterPassword: $request->getParameter(self::FIELD_MASTER_PASSWORD),
            isAdmin: $request->getParameter('isAdmin', '0') === '1',
        );

        return $this->jsonResponse([], HttpStatusCode::CREATED);
    }

    /**
     * @param RequestInterface $request
     * @param array $parameters
     * @return ResponseInterface
     * @throws JsonException
     * @throws PersistenceException
     */
    public function show(RequestInterface $request, array $parameters): ResponseInterface
    {

        return $this->jsonResponse(
            $this->normalizeData(
                data: $this->userApplicationService->getUserByUuid($parameters['id']),
                entityResponseClassName: UserResponse::class,
            )
        );
    }

    /**
     * @param RequestInterface $request
     * @param array $parameters
     *
     * @return ResponseInterface
     * @throws JsonException
     * @throws Throwable
     */
    public function update(RequestInterface $request, array $parameters): ResponseInterface
    {
        $httpRequestValidator = new HttpRequestValidator();
        $passwordMinLength = Config::getInstance()->getByPath('security.password_min_length', 8);
        $httpRequestValidator->addRules([
            UserRepository::FIELD_USER_NAME => [
                new NotEmptyRule(),
                new MinLengthRule(2),
                new MaxLengthRule(255)
            ],
            self::FIELD_PASSWORD => [
                new MinLengthRule($passwordMinLength),
            ],
        ]);

        $this->userApplicationService->updateUser(
            uuid: $parameters['id'],
            userName: $request->getParameter(UserRepository::FIELD_USER_NAME),
            password: $request->getParameter(self::FIELD_PASSWORD),
        );

        return $this->jsonResponse([], HttpStatusCode::NO_CONTENT);
    }

    /**
     * @param RequestInterface $request
     * @param array $parameters
     * @return ResponseInterface
     * @throws JsonException
     * @throws Throwable
     */
    public function destroy(RequestInterface $request, array $parameters): ResponseInterface
    {
        $this->userApplicationService->deleteUser($parameters['id']);
        return $this->jsonResponse([], HttpStatusCode::NO_CONTENT);
    }
}