<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Functional\Infrastructure\Http\Controller;

use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\Identity\Infrastructure\Http\Controller\UserController;
use App\Module\PasswordBroker\Domain\Entry\ValueObject\Title;
use App\Module\PasswordBroker\Infrastructure\Entry\Repository\EntryRepository;
use App\Module\PasswordBroker\Infrastructure\Http\Controller\EntryController;
use App\Module\PasswordBroker\Infrastructure\Http\Route\EntryGroupRoute;
use App\Module\PasswordBroker\Infrastructure\Http\Route\EntryRoute;
use App\Module\PasswordBroker\Infrastructure\Http\Route\PasswordBrokerRoute;
use App\Shared\Infrastructure\Http\Route\AppRoute;
use Inquisition\Core\Infrastructure\Http\Controller\RestControllerInterface;
use Inquisition\Core\Infrastructure\Http\HttpStatusCode;
use Inquisition\Core\Infrastructure\Http\Router\Exception\RouteNotFoundException;
use Inquisition\Core\Infrastructure\Http\Router\Router;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use ReflectionException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupUserFixture;
use Tests\Shared\FunctionalTestCase;

class EntryControllerTest extends FunctionalTestCase
{
    private array $routePath;
    private User $userActor;

    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     */
    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->routePath = [
            AppRoute::GROUP_NAME,
            PasswordBrokerRoute::GROUP_NAME,
            EntryGroupRoute::GROUP_NAME,
            EntryRoute::GROUP_NAME,
        ];

        $this->userActor = UserFixture::create(persist: true);
        $this->actAs($this->userActor);
    }

    /**
     * @throws PersistenceException
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_return_entries_in_entry_group(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $this->userActor->getId()->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->getId()->toRaw(),
            ],
            persist: true,
        );
        $entry = EntryFixture::create(attributes: [EntryFixture::ENTRY_GROUP => $entryGroup], persist: true);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_INDEX);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
        );

        $this->assertEquals(HttpStatusCode::OK, $httpResponse->getStatusCode());
        $content = $httpResponse->getContent();
        $this->assertJson($content);
        $response = json_decode($content, true);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('pagination', $response);
        $this->assertCount(1, $response['data']);
        $this->assertEquals($entry->id->toRaw(), $response['data'][0][EntryRepository::FIELD_ID]);
        $this->assertArrayHasKey('total', $response['pagination']);
        $this->assertEquals(1, $response['pagination']['total']);
    }

    /**
     * @throws PersistenceException
     * @throws RouteNotFoundException
     */
    public function test_index_should_return_403_for_users_not_in_entry_group(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        EntryFixture::create(attributes: [EntryFixture::ENTRY_GROUP => $entryGroup], persist: true);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_INDEX);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
        );

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());
    }

    /**
     * @throws PersistenceException
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_show_entry(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $this->userActor->getId()->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->getId()->toRaw(),
            ],
            persist: true,
        );
        $entry = EntryFixture::create(attributes: [EntryFixture::ENTRY_GROUP => $entryGroup], persist: true);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_SHOW);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
                EntryRoute::PARAM_ENTRY_ID => $entry->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
        );

        $this->assertEquals(HttpStatusCode::OK, $httpResponse->getStatusCode());
        $content = $httpResponse->getContent();
        $this->assertJson($content);
        $response = json_decode($content, true);
        $this->assertArrayHasKey(EntryRepository::FIELD_ID, $response);
        $this->assertEquals($entry->id->toRaw(), $response[EntryRepository::FIELD_ID]);
        $this->assertArrayHasKey(EntryRepository::FIELD_ENTRY_GROUP_ID, $response);
        $this->assertEquals($entryGroup->id->toRaw(), $response[EntryRepository::FIELD_ENTRY_GROUP_ID]);
        $this->assertArrayHasKey(EntryRepository::FIELD_TITLE, $response);
        $this->assertEquals($entry->title->toRaw(), $response[EntryRepository::FIELD_TITLE]);
        $this->assertArrayHasKey(EntryRepository::FIELD_CREATED_AT, $response);
        $this->assertArrayHasKey(EntryRepository::FIELD_UPDATED_AT, $response);
        $this->assertArrayHasKey(EntryRepository::FIELD_DELETED_AT, $response);
        $this->assertNull($response[EntryRepository::FIELD_DELETED_AT]);
    }

    /**
     * @throws PersistenceException
     * @throws RouteNotFoundException
     */
    public function test_show_should_return_403_for_users_not_in_entry_group(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        $entry = EntryFixture::create(attributes: [EntryFixture::ENTRY_GROUP => $entryGroup], persist: true);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_SHOW);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
                EntryRoute::PARAM_ENTRY_ID => $entry->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
        );

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_create_entry(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $this->userActor->getId()->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->getId()->toRaw(),
            ],
            persist: true,
        );
        $entry = EntryFixture::create(attributes: [EntryFixture::ENTRY_GROUP => $entryGroup]);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_STORE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: $entry->getAsArray(),
        );

        $this->assertEquals(HttpStatusCode::CREATED, $httpResponse->getStatusCode());
        $this->assertDatabaseHas(EntryFixture::getTableName(), [
            EntryFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            EntryFixture::TITLE => $entry->title->toRaw(),
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_create_entry_should_return_403_for_user_not_in_group(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        $entry = EntryFixture::create(attributes: [EntryFixture::ENTRY_GROUP => $entryGroup]);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_STORE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: $entry->getAsArray(),
        );

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());
        $this->assertDatabaseMissing(EntryFixture::getTableName(), [
            EntryFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            EntryFixture::TITLE => $entry->title->toRaw(),
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_update_entry(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $this->userActor->getId()->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->getId()->toRaw(),
            ],
            persist: true,
        );
        $entry = EntryFixture::create(attributes: [EntryFixture::ENTRY_GROUP => $entryGroup], persist: true);
        $oldTitle = $entry->title;
        $entry->title = Title::fromRaw('Updated title');

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_UPDATE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
                EntryRoute::PARAM_ENTRY_ID => $entry->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: $entry->getAsArray(),
        );

        $this->assertEquals(HttpStatusCode::NO_CONTENT, $httpResponse->getStatusCode());
        $this->assertDatabaseHas(EntryFixture::getTableName(), [
            EntryFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            EntryFixture::TITLE => $entry->title->toRaw(),
        ]);
        $this->assertDatabaseMissing(EntryFixture::getTableName(), [
            EntryFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            EntryFixture::TITLE => $oldTitle->toRaw(),
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws PersistenceException
     */
    public function test_update_entry_should_return_403_for_user_not_in_group(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        $entry = EntryFixture::create(attributes: [EntryFixture::ENTRY_GROUP => $entryGroup], persist: true);
        $oldTitle = $entry->title;
        $entry->title = Title::fromRaw('Updated title');

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_UPDATE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
                EntryRoute::PARAM_ENTRY_ID => $entry->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: $entry->getAsArray(),
        );

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());
        $this->assertDatabaseMissing(EntryFixture::getTableName(), [
            EntryFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            EntryFixture::TITLE => $entry->title->toRaw(),
        ]);
        $this->assertDatabaseHas(EntryFixture::getTableName(), [
            EntryFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            EntryFixture::TITLE => $oldTitle->toRaw(),
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_soft_delete_entry(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $this->userActor->getId()->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->getId()->toRaw(),
            ],
            persist: true,
        );
        $entry = EntryFixture::create(attributes: [EntryFixture::ENTRY_GROUP => $entryGroup], persist: true);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_DESTROY);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
                EntryRoute::PARAM_ENTRY_ID => $entry->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
        );

        $this->assertEquals(HttpStatusCode::NO_CONTENT, $httpResponse->getStatusCode());
        $this->assertDatabaseMissing(EntryFixture::getTableName(), [
            EntryFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            EntryFixture::TITLE => $entry->title->toRaw(),
            EntryFixture::DELETED_AT => null,
        ]);
        $this->assertDatabaseHas(EntryFixture::getTableName(), [
            EntryFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            EntryFixture::TITLE => $entry->title->toRaw(),
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_soft_delete_entry_should_return_403_for_user_not_in_group(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        $entry = EntryFixture::create(attributes: [EntryFixture::ENTRY_GROUP => $entryGroup], persist: true);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_DESTROY);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
                EntryRoute::PARAM_ENTRY_ID => $entry->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
        );

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());
        $this->assertDatabaseHas(EntryFixture::getTableName(), [
            EntryFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            EntryFixture::TITLE => $entry->title->toRaw(),
            EntryFixture::DELETED_AT => null,
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_move_entry_to_another_group(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        $entryGroupTarget = EntryGroupFixture::create(persist: true);
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $this->userActor->getId()->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->getId()->toRaw(),
            ],
            persist: true,
        );
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $this->userActor->getId()->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupTarget->getId()->toRaw(),
            ],
            persist: true,
        );
        $entry = EntryFixture::create(attributes: [EntryFixture::ENTRY_GROUP => $entryGroup], persist: true);

        $routeName = $this->buildRouteName($this->routePath, EntryController::ACTION_MOVE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
                EntryRoute::PARAM_ENTRY_ID => $entry->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: [
                EntryController::FIELD_TARGET_ENTRY_GROUP_ID => $entryGroupTarget->id->toRaw(),
                UserController::FIELD_MASTER_PASSWORD => UserFixture::DEFAULT_MASTER_PASSWORD,
            ],
        );

        $this->assertEquals(HttpStatusCode::NO_CONTENT, $httpResponse->getStatusCode());
        $this->assertDatabaseHas(EntryFixture::getTableName(), [
            EntryFixture::ENTRY_GROUP_ID => $entryGroupTarget->id->toRaw(),
            EntryFixture::TITLE => $entry->title->toRaw(),
        ]);
        $this->assertDatabaseMissing(EntryFixture::getTableName(), [
            EntryFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            EntryFixture::TITLE => $entry->title->toRaw(),
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_move_entry_to_another_group_should_return_error_bad_request_if_user_not_in_target_group(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        $entryGroupTarget = EntryGroupFixture::create(persist: true);
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $this->userActor->getId()->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->getId()->toRaw(),
            ],
            persist: true,
        );
        $entry = EntryFixture::create(attributes: [EntryFixture::ENTRY_GROUP => $entryGroup], persist: true);

        $routeName = $this->buildRouteName($this->routePath, EntryController::ACTION_MOVE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
                EntryRoute::PARAM_ENTRY_ID => $entry->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: [
                EntryController::FIELD_TARGET_ENTRY_GROUP_ID => $entryGroupTarget->id->toRaw(),
                UserController::FIELD_MASTER_PASSWORD => UserFixture::DEFAULT_MASTER_PASSWORD,
            ],
        );

        $this->assertEquals(HttpStatusCode::BAD_REQUEST, $httpResponse->getStatusCode());

        $this->assertDatabaseHas(EntryFixture::getTableName(), [
            EntryFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            EntryFixture::TITLE => $entry->title->toRaw(),
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_move_entry_to_another_group_should_return_403_if_user_not_in_group(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        $entryGroupTarget = EntryGroupFixture::create(persist: true);
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $this->userActor->getId()->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupTarget->getId()->toRaw(),
            ],
            persist: true,
        );
        $entry = EntryFixture::create(attributes: [EntryFixture::ENTRY_GROUP => $entryGroup], persist: true);

        $routeName = $this->buildRouteName($this->routePath, EntryController::ACTION_MOVE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
                EntryRoute::PARAM_ENTRY_ID => $entry->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: [
                EntryController::FIELD_TARGET_ENTRY_GROUP_ID => $entryGroupTarget->id->toRaw(),
                UserController::FIELD_MASTER_PASSWORD => UserFixture::DEFAULT_MASTER_PASSWORD,
            ],
        );

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());

        $this->assertDatabaseHas(EntryFixture::getTableName(), [
            EntryFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            EntryFixture::TITLE => $entry->title->toRaw(),
        ]);
    }
}
