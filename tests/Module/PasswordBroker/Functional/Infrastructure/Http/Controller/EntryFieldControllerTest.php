<?php

declare(strict_types=1);

namespace PasswordBroker\Functional\Infrastructure\Http\Controller;

use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\Identity\Infrastructure\Http\Controller\UserController;
use App\Module\PasswordBroker\Domain\Entry\Entity\Entry;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Enum\RoleEnum;
use App\Module\PasswordBroker\Infrastructure\Entry\Repository\EntryRepository;
use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use App\Module\PasswordBroker\Infrastructure\Http\Controller\EntryFieldController;
use App\Module\PasswordBroker\Infrastructure\Http\Route\EntryFieldRoute;
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
use Tests\Module\PasswordBroker\Fixture\EntryFieldFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupUserFixture;
use Tests\Shared\FunctionalTestCase;

class EntryFieldControllerTest extends FunctionalTestCase
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
            EntryFieldRoute::GROUP_NAME,
        ];

        $this->userActor = UserFixture::create(persist: true);
        $this->actAs($this->userActor);
    }

    /**
     * @throws PersistenceException
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_return_entry_fields_in_entry(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(attributes: [EntryFieldFixture::ENTRY => $entry], persist: true);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_INDEX);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupRoute::PARAM_ENTRY_GROUP_ID => $entry->entryGroupId->toRaw(),
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
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('pagination', $response);
        $this->assertCount(1, $response['data']);
        $this->assertEquals($entryField->id->toRaw(), $response['data'][0][EntryFieldRepository::FIELD_ID]);
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
        $entry = EntryFixture::create(attributes: [EntryFixture::ENTRY_GROUP => $entryGroup], persist: true);
        EntryFieldFixture::create(attributes: [EntryFieldFixture::ENTRY => $entry], persist: true);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_INDEX);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupRoute::PARAM_ENTRY_GROUP_ID => $entry->entryGroupId->toRaw(),
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
     * @throws PersistenceException
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_show_entry_field(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(attributes: [EntryFieldFixture::ENTRY => $entry], persist: true);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_SHOW);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupRoute::PARAM_ENTRY_GROUP_ID => $entry->entryGroupId->toRaw(),
                EntryRoute::PARAM_ENTRY_ID => $entry->id->toRaw(),
                EntryFieldRoute::PARAM_ENTRY_FIELD_ID => $entryField->id->toRaw(),
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
        $this->assertArrayHasKey(EntryFieldRepository::FIELD_ID, $response);
        $this->assertEquals($entryField->id->toRaw(), $response[EntryRepository::FIELD_ID]);
        $this->assertArrayHasKey(EntryFieldRepository::FIELD_ENTRY_ID, $response);
        $this->assertEquals($entry->id->toRaw(), $response[EntryFieldRepository::FIELD_ENTRY_ID]);
        $this->assertArrayHasKey(EntryFieldRepository::FIELD_TITLE, $response);
        $this->assertEquals($entryField->title->toRaw(), $response[EntryFieldRepository::FIELD_TITLE]);
        $this->assertArrayHasKey(EntryFieldRepository::FIELD_CREATED_AT, $response);
        $this->assertArrayHasKey(EntryFieldRepository::FIELD_UPDATED_AT, $response);
        $this->assertArrayHasKey(EntryFieldRepository::FIELD_DELETED_AT, $response);
        $this->assertNull($response[EntryFieldRepository::FIELD_DELETED_AT]);
    }

    /**
     * @throws PersistenceException
     * @throws RouteNotFoundException
     */
    public function test_show_should_return_403_for_users_not_in_entry_group(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        $entry = EntryFixture::create(attributes: [EntryFixture::ENTRY_GROUP => $entryGroup], persist: true);
        $entryField = EntryFieldFixture::create(attributes: [EntryFieldFixture::ENTRY => $entry], persist: true);

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
                EntryFieldRoute::PARAM_ENTRY_FIELD_ID => $entryField->id->toRaw(),
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
    public function test_it_should_create_entry_field(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(attributes: [EntryFieldFixture::ENTRY => $entry]);
        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_STORE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupRoute::PARAM_ENTRY_GROUP_ID => $entry->entryGroupId->toRaw(),
                EntryRoute::PARAM_ENTRY_ID => $entry->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: [
                ...$entryField->getAsArray(),
                UserController::FIELD_MASTER_PASSWORD => UserFixture::DEFAULT_MASTER_PASSWORD,
                EntryFieldController::FIELD_VALUE => 'New value',
            ],
        );

        $this->assertEquals(HttpStatusCode::CREATED, $httpResponse->getStatusCode());
        $this->assertDatabaseHas(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ENTRY_ID => $entry->id->toRaw(),
            EntryFieldFixture::TITLE => $entryField->title->toRaw(),
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
        $entryField = EntryFieldFixture::create(attributes: [EntryFieldFixture::ENTRY => $entry]);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_STORE);
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
                ...$entryField->getAsArray(),
                UserController::FIELD_MASTER_PASSWORD => UserFixture::DEFAULT_MASTER_PASSWORD,
                EntryFieldController::FIELD_VALUE => 'New value',
            ],
        );

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());
        $this->assertDatabaseMissing(EntryFixture::getTableName(), [
            EntryFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            EntryFixture::TITLE => $entry->title->toRaw(),
        ]);
    }

    /**
     * @throws PersistenceException
     * @throws RsaDomainServiceException
     */
    private function createAnEntry(): Entry
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $this->userActor->getId()->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->getId()->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::MEMBER->value,
            ],
            persist: true,
        );
        return EntryFixture::create(attributes: [EntryFixture::ENTRY_GROUP => $entryGroup], persist: true);
    }
}
