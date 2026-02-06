<?php

namespace Tests\Module\PasswordBroker\Functional\Infrastructure\Http\Controller;

use App\Module\PasswordBroker\Application\EntryGroup\DTO\EntryGroupTreeResponse;
use App\Module\PasswordBroker\Infrastructure\Http\Route\EntryGroupRoute;
use App\Module\PasswordBroker\Infrastructure\Http\Route\PasswordBrokerRoute;
use App\Shared\Infrastructure\Http\Route\AppRoute;
use Inquisition\Core\Infrastructure\Http\Controller\RestControllerInterface;
use Inquisition\Core\Infrastructure\Http\HttpStatusCode;
use Inquisition\Core\Infrastructure\Http\Router\Exception\RouteNotFoundException;
use Inquisition\Core\Infrastructure\Http\Router\Router;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use ReflectionException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupFixture;
use Tests\Shared\FunctionalTestCase;

class EntryGroupControllerTest extends FunctionalTestCase
{
    private array $routePath;

    /**
     * @return void
     * @throws PersistenceException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->routePath = [
            AppRoute::GROUP_NAME,
            PasswordBrokerRoute::GROUP_NAME,
            EntryGroupRoute::GROUP_NAME,
        ];
    }

    /**
     * @return void
     * @throws PersistenceException
     * @throws RouteNotFoundException
     * @throws ReflectionException
     */
    public function testItShouldReturnATreeView(): void
    {
        $tree = EntryGroupFixture::createTree();
        $treeSecond = EntryGroupFixture::createTree();
        $this->actAs(UserFixture::create(attributes: [UserFixture::IS_ADMIN => true], persist: true));

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_INDEX);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path
        );

        $httpResponse = $this->sendRequest($httpMethod, $uri);

        $this->assertEquals(HttpStatusCode::OK, $httpResponse->getStatusCode());

        $content = $httpResponse->getContent();
        $this->assertJson($content);

        $response = json_decode($content, true);
        $this->assertArrayHasKey(EntryGroupTreeResponse::FIELD_TREES, $response);
        $treeResponse = $response[EntryGroupTreeResponse::FIELD_TREES];
        $this->assertArrayHasKey(array_key_first($tree), $treeResponse);
        $this->assertArrayHasKey(array_key_first($treeSecond), $treeResponse);
    }
}