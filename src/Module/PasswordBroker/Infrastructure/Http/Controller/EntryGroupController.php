<?php

namespace App\Module\PasswordBroker\Infrastructure\Http\Controller;

use App\Module\Identity\Application\User\Service\Exception\AuthException;
use App\Module\PasswordBroker\Application\EntryGroup\DTO\EntryGroupTreeResponse;
use App\Module\PasswordBroker\Application\EntryGroup\Service\EntryGroupApplicationService;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\EntryGroupRepository;
use App\Shared\Application\Validation\Rule\ValidUuidRule;
use Inquisition\Core\Application\Validation\Exception\ValidationException;
use Inquisition\Core\Application\Validation\HttpRequestValidator;
use Inquisition\Core\Application\Validation\Rule\MaxLengthRule;
use Inquisition\Core\Application\Validation\Rule\NotEmptyRule;
use Inquisition\Core\Infrastructure\Http\Controller\AbstractRestController;
use Inquisition\Core\Infrastructure\Http\Controller\RestControllerInterface;
use Inquisition\Core\Infrastructure\Http\HttpStatusCode;
use Inquisition\Core\Infrastructure\Http\Request\RequestInterface;
use Inquisition\Core\Infrastructure\Http\Response\ResponseInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use JsonException;
use Throwable;

final readonly class EntryGroupController extends AbstractRestController
    implements RestControllerInterface
{
    public const string ACTION_MOVE = 'move';

    private EntryGroupApplicationService $entryGroupApplicationService;

    public function __construct()
    {
        $this->entryGroupApplicationService = EntryGroupApplicationService::getInstance();
    }

    /**
     * @param RequestInterface $request
     * @param array $parameters
     * @return ResponseInterface
     * @throws AuthException
     * @throws PersistenceException
     * @throws JsonException
     */
    public function index(RequestInterface $request, array $parameters): ResponseInterface
    {
        $tree = $this->entryGroupApplicationService->getEntryGroupsAsTree();

        return $this->jsonResponse(
            $this->normalizeData(
                data: $tree,
                entityResponseClassName: EntryGroupTreeResponse::class,
            )
        );
    }

    /**
     * @param RequestInterface $request
     * @param array $parameters
     * @return ResponseInterface
     * @throws JsonException
     * @throws PersistenceException
     * @throws ValidationException
     * @throws Throwable
     */
    public function store(RequestInterface $request, array $parameters): ResponseInterface
    {
        new HttpRequestValidator()->addRules([
            EntryGroupRepository::FIELD_NAME => [
                new NotEmptyRule(),
                new MaxLengthRule(255),
            ],
            EntryGroupRepository::FIELD_PARENT_ENTRY_GROUP_ID => [
                new ValidUuidRule()
            ]
        ])->validate($request);

        $this->entryGroupApplicationService->createEntryGroupFromPrimitivesSync(
            name: $request->getParameter(EntryGroupRepository::FIELD_NAME),
            parentEntryGroupId: $request->getParameter(EntryGroupRepository::FIELD_PARENT_ENTRY_GROUP_ID),
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
                data: $this->entryGroupApplicationService->getEntryGroupByUuid($parameters['id']),
                entityResponseClassName: EntryGroupRepository::class,
            )
        );
    }

    /**
     * @param RequestInterface $request
     * @param array $parameters
     * @return ResponseInterface
     * @throws JsonException
     * @throws PersistenceException
     * @throws ValidationException
     */
    public function update(RequestInterface $request, array $parameters): ResponseInterface
    {
        new HttpRequestValidator()->addRules([
            EntryGroupRepository::FIELD_NAME => [
                new NotEmptyRule(),
                new MaxLengthRule(255),
            ],
        ])->validate($request);

        $this->entryGroupApplicationService->renameEntryGroupSync(
            uuid: $parameters['id'],
            name: $request->getParameter(EntryGroupRepository::FIELD_NAME),
        );

        return $this->jsonResponse([], HttpStatusCode::NO_CONTENT);
    }

    /**
     * @param RequestInterface $request
     * @param array $parameters
     * @return ResponseInterface
     * @throws JsonException
     * @throws PersistenceException
     */
    public function destroy(RequestInterface $request, array $parameters): ResponseInterface
    {
        $this->entryGroupApplicationService->deleteEntryGroupSync($parameters['id']);

        return $this->jsonResponse([], HttpStatusCode::NO_CONTENT);
    }

    /**
     * @param RequestInterface $request
     * @param array $parameters
     * @return ResponseInterface
     * @throws JsonException
     * @throws PersistenceException
     * @throws ValidationException
     */
    public function move(RequestInterface $request, array $parameters): ResponseInterface
    {
        new HttpRequestValidator()->addRules([
            'targetId' => [
                new ValidUuidRule()
            ]
        ])->validate($request);

        $this->entryGroupApplicationService->moveEntryGroupSync(
            uuid: $parameters['id'],
            targetUuid: $request->getParameter('targetId'),
        );

        return $this->jsonResponse([], HttpStatusCode::NO_CONTENT);
    }
}