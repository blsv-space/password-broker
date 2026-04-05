<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Http\Controller;

use App\Module\Identity\Application\User\Service\Exception\AuthException;
use App\Module\PasswordBroker\Application\EntryGroup\DTO\EntryGroupResponse;
use App\Module\PasswordBroker\Application\EntryGroup\DTO\EntryGroupTreeResponse;
use App\Module\PasswordBroker\Application\EntryGroup\Service\EntryGroupApplicationService;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\Repository\EntryGroupRepository;
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
    public const string FIELD_QUERY = 'query';

    private EntryGroupApplicationService $entryGroupApplicationService;

    public function __construct()
    {
        $this->entryGroupApplicationService = EntryGroupApplicationService::getInstance();
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
                data: $this->entryGroupApplicationService->getEntryGroupByUuid($parameters['id']),
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
            uuid: $parameters['id'],
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
        $this->entryGroupApplicationService->deleteEntryGroupSync($parameters['id']);

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
            'targetId' => [
                new ValidUuidRule(),
            ],
        ])->validate($request);

        $this->entryGroupApplicationService->moveEntryGroupSync(
            uuid: $parameters['id'],
            targetUuid: $request->getParameter('targetId'),
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
}
