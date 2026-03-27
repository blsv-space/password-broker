<?php

declare(strict_types=1);

namespace Tests\Module\Identity\Functional\Infrastructure\Http\Controller;

use App\Module\Identity\Infrastructure\Http\Controller\AuthController;
use App\Module\Identity\Infrastructure\Http\Route\AuthRoute;
use App\Module\Identity\Infrastructure\Http\Route\IdentityRoute;
use App\Module\Identity\Infrastructure\Security\PasswordHasher;
use App\Shared\Infrastructure\Http\Route\AppRoute;
use Inquisition\Core\Infrastructure\Http\HttpStatusCode;
use Inquisition\Core\Infrastructure\Http\Router\Exception\RouteNotFoundException;
use Inquisition\Core\Infrastructure\Http\Router\Router;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use ReflectionException;
use Tests\Module\Identity\Fixture\RefreshTokenFixture;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Shared\FunctionalTestCase;

class AuthControllerTest extends FunctionalTestCase
{
    private array $routePath;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->routePath = [
            AppRoute::GROUP_NAME,
            IdentityRoute::GROUP_NAME,
            AuthRoute::GROUP_NAME,
        ];
    }

    /**
     * @throws RouteNotFoundException
     * @throws PersistenceException
     */
    public function test_it_should_login(): void
    {
        $userName = $this->faker->userName();
        $password = $this->faker->password();
        $hashedPassword = PasswordHasher::getInstance()->hash($password);
        $user = UserFixture::create(
            attributes: [
                UserFixture::USER_NAME => $userName,
                UserFixture::HASHED_PASSWORD => $hashedPassword,
            ],
            persist: true,
        );
        $this->assertDatabaseHas(UserFixture::getTableName(), [
            UserFixture::USER_NAME => $userName,
        ]);

        $routeName = $this->buildRouteName($this->routePath, AuthController::ACTION_LOGIN);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");
        $httpResponse = $this->sendRequest($httpMethod, $route->path, [
            'userName' => $userName,
            'password' => $password,
        ]);

        $this->assertEquals(HttpStatusCode::OK, $httpResponse->getStatusCode());
        $content = $httpResponse->getContent();
        $this->assertJson($content);
        $response = json_decode($content, true);

        $this->assertArrayHasKey('jwtToken', $response);
        $this->assertArrayHasKey('refreshToken', $response);
        $this->assertNotEmpty($response['jwtToken']);
        $this->assertNotEmpty($response['refreshToken']);
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
            RefreshTokenFixture::TOKEN => $response['refreshToken'],
        ]);
    }

    /**
     * @throws PersistenceException
     * @throws RouteNotFoundException
     */
    public function test_it_should_not_login_without_password(): void
    {
        $userName = $this->faker->userName();
        UserFixture::create(
            attributes: [
                UserFixture::USER_NAME => $userName,
            ],
            persist: true,
        );
        $this->assertDatabaseHas(UserFixture::getTableName(), [
            UserFixture::USER_NAME => $userName,
        ]);
        $routeName = $this->buildRouteName($this->routePath, AuthController::ACTION_LOGIN);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");
        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $route->path,
            body: [
                'userName' => $userName,
            ],
        );

        $this->assertEquals(HttpStatusCode::BAD_REQUEST, $httpResponse->getStatusCode());
    }

    /**
     * @throws PersistenceException
     * @throws RouteNotFoundException
     * @throws ReflectionException
     */
    public function test_it_should_logout(): void
    {
        $user = UserFixture::create(persist: true);
        $this->actAs($user);
        RefreshTokenFixture::create(
            attributes: [RefreshTokenFixture::USER_ID => $user->id->toRaw()],
            persist: true,
        );
        $this->assertDatabaseHas(
            table: RefreshTokenFixture::getTableName(),
            param: [RefreshTokenFixture::USER_ID => $user->id->toRaw()],
        );
        $routeName = $this->buildRouteName($this->routePath, AuthController::ACTION_LOGOUT);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");
        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $route->path,
        );

        $this->assertEquals(HttpStatusCode::NO_CONTENT, $httpResponse->getStatusCode());
        $this->assertDatabaseMissing(
            table: RefreshTokenFixture::getTableName(),
            param: [RefreshTokenFixture::USER_ID => $user->id->toRaw()],
        );
    }

    /**
     * @throws PersistenceException
     * @throws ReflectionException
     * @throws RouteNotFoundException
     */
    public function test_it_should_refresh_token(): void
    {
        $user = UserFixture::create(persist: true);
        $this->actAs($user);

        $refreshToken = RefreshTokenFixture::create(
            attributes: [RefreshTokenFixture::USER_ID => $user->id->toRaw()],
            persist: true,
        );

        $routeName = $this->buildRouteName($this->routePath, AuthController::ACTION_REFRESH_TOKEN);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");
        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $route->path,
            body: [
                'refreshToken' => $refreshToken->token->toRaw(),
            ],
        );

        $this->assertEquals(HttpStatusCode::OK, $httpResponse->getStatusCode());
        $content = $httpResponse->getContent();
        $this->assertJson($content);
        $response = json_decode($content, true);

        $this->assertArrayHasKey('jwtToken', $response);
        $this->assertNotEmpty($response['jwtToken']);
        $this->assertArrayHasKey('refreshToken', $response);
        $this->assertNotEmpty($response['refreshToken']);
        $this->assertNotEquals($refreshToken->token->toRaw(), $response['refreshToken']);
        $this->assertDatabaseMissing(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
            RefreshTokenFixture::TOKEN => $refreshToken->token->toRaw(),
        ]);
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
            RefreshTokenFixture::TOKEN => $response['refreshToken'],
        ]);
    }
}
