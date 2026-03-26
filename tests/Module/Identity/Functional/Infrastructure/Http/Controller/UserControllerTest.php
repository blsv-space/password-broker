<?php

declare(strict_types=1);

namespace Tests\Module\Identity\Functional\Infrastructure\Http\Controller;

use App\Module\Identity\Infrastructure\Http\Controller\UserController;
use App\Module\Identity\Infrastructure\Http\Route\IdentityRoute;
use App\Module\Identity\Infrastructure\Http\Route\UserRoute;
use App\Shared\Infrastructure\Http\Route\AppRoute;
use Inquisition\Core\Infrastructure\Http\Controller\AbstractRestController;
use Inquisition\Core\Infrastructure\Http\Controller\RestControllerInterface;
use Inquisition\Core\Infrastructure\Http\HttpStatusCode;
use Inquisition\Core\Infrastructure\Http\Router\Exception\RouteNotFoundException;
use Inquisition\Core\Infrastructure\Http\Router\Router;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use ReflectionException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Shared\FunctionalTestCase;

class UserControllerTest extends FunctionalTestCase
{
    private array $routePath;

    /**
     * @throws PersistenceException
     */
    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->routePath = [
            AppRoute::GROUP_NAME,
            IdentityRoute::GROUP_NAME,
            UserRoute::GROUP_NAME,
        ];
    }


    /**
     * @throws PersistenceException
     * @throws RouteNotFoundException
     * @throws ReflectionException
     */
    public function test_it_should_list_users(): void
    {
        $userNumber = $this->faker->numberBetween(3, 10);
        $this->actAs(UserFixture::createMany($userNumber, persist: true)[0]);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_INDEX);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            queryParams: [
                AbstractRestController::PER_PAGE_PARAM => 20,
                AbstractRestController::PAGE_PARAM => 1,
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
        $this->assertCount($userNumber, $response['data']);
        $this->assertArrayHasKey('total', $response['pagination']);
        $this->assertEquals($userNumber, $response['pagination']['total']);
    }

    /**
     * @throws PersistenceException
     * @throws ReflectionException
     * @throws RouteNotFoundException
     */
    public function test_it_should_found_user(): void
    {
        $userNameTarget = 'aaaaa';
        $userName_1 = 'bbbbb';
        $userName_2 = 'ccccc';

        UserFixture::create(
            attributes: [UserFixture::USER_NAME => $userNameTarget],
            persist: true,
        );
        $user = UserFixture::create(
            attributes: [UserFixture::USER_NAME => $userName_1],
            persist: true,
        );
        UserFixture::create(
            attributes: [UserFixture::USER_NAME => $userName_2],
            persist: true,
        );

        $this->actAs($user);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_INDEX);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            queryParams: [
                AbstractRestController::PER_PAGE_PARAM => 20,
                AbstractRestController::PAGE_PARAM => 1,
                UserFixture::USER_NAME => $userNameTarget,
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
        $this->assertEquals($userNameTarget, $response['data'][0][UserFixture::USER_NAME]);
        $this->assertArrayHasKey('total', $response['pagination']);
        $this->assertEquals(1, $response['pagination']['total']);
    }

    /**
     * @throws PersistenceException
     * @throws ReflectionException
     * @throws RouteNotFoundException
     */
    public function test_it_should_create_user(): void
    {
        $userActor = UserFixture::create(persist: true);
        $userForCreating = UserFixture::create();
        $this->actAs($userActor);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_STORE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $route->path,
            body: [
                UserFixture::USER_NAME => $userForCreating->userName->toRaw(),
                UserController::FIELD_PASSWORD => $this->faker->password(),
                UserController::FIELD_MASTER_PASSWORD => $this->faker->password(),
                UserController::FIELD_EMAIL => $this->faker->email(),
                UserController::FIELD_IS_ADMIN => $this->faker->boolean(),
            ],
        );

        $this->assertEquals(HttpStatusCode::CREATED, $httpResponse->getStatusCode());

        $this->assertDatabaseHas(UserFixture::getTableName(), [
            UserFixture::USER_NAME => $userForCreating->userName->toRaw(),
        ]);
    }

    /**
     * @throws PersistenceException
     * @throws ReflectionException
     * @throws RouteNotFoundException
     */
    public function test_it_should_show_user(): void
    {
        $user = UserFixture::create(persist: true);
        $this->actAs($user);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_SHOW);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: ['id' => $user->id->toRaw()],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
        );

        $this->assertEquals(HttpStatusCode::OK, $httpResponse->getStatusCode());
        $content = $httpResponse->getContent();
        $this->assertJson($content);
        $response = json_decode($content, true);
        $this->assertEquals($user->userName->toRaw(), $response[UserFixture::USER_NAME]);
    }

    /**
     * @throws PersistenceException
     * @throws ReflectionException
     * @throws RouteNotFoundException
     */
    public function test_it_should_not_show_user_hashed_password(): void
    {
        $user = UserFixture::create(persist: true);
        $this->actAs($user);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_SHOW);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: ['id' => $user->id->toRaw()],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
        );

        $this->assertEquals(HttpStatusCode::OK, $httpResponse->getStatusCode());
        $content = $httpResponse->getContent();
        $this->assertJson($content);
        $response = json_decode($content, true);
        $this->assertArrayNotHasKey(UserController::FIELD_PASSWORD, $response);
    }

    /**
     * @throws PersistenceException
     * @throws ReflectionException
     * @throws RouteNotFoundException
     */
    public function test_it_should_update_user(): void
    {
        $user = UserFixture::create(persist: true);
        $this->actAs($user);
        $newUserName = $this->faker->userName();

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_UPDATE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: ['id' => $user->id->toRaw()],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: [
                UserFixture::USER_NAME => $newUserName,
            ],
        );

        $this->assertEquals(HttpStatusCode::NO_CONTENT, $httpResponse->getStatusCode());
        $this->assertDatabaseHas(UserFixture::getTableName(), [
            UserFixture::ID => $user->id->toRaw(),
            UserFixture::USER_NAME => $newUserName,
        ]);
        $this->assertDatabaseMissing(UserFixture::getTableName(), [
            UserFixture::USER_NAME => $user->userName->toRaw(),
        ]);
    }

    /**
     * @throws PersistenceException
     * @throws ReflectionException
     * @throws RouteNotFoundException
     */
    public function test_it_should_delete_user(): void
    {
        $user = UserFixture::create(persist: true);
        $this->actAs($user);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_DESTROY);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: ['id' => $user->id->toRaw()],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
        );

        $this->assertEquals(HttpStatusCode::NO_CONTENT, $httpResponse->getStatusCode());
        $this->assertDatabaseMissing(
            table: UserFixture::getTableName(),
            param: [
                UserFixture::ID => $user->id->toRaw(),
                UserFixture::DELETED_AT => null,
            ],
        );
    }
}
