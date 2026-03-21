<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Functional\Infrastructure\Http\Controller;

use App\Module\PasswordBroker\Application\EntryGroup\DTO\EntryGroupTreeResponse;
use App\Module\PasswordBroker\Domain\EntryGroup\DTO\EntryGroupTreeNode;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\EntryGroupRepository;
use App\Module\PasswordBroker\Infrastructure\Http\Controller\EntryGroupController;
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
     * @throws PersistenceException
     */
    #[\Override]
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
     * @throws PersistenceException
     * @throws RouteNotFoundException
     * @throws ReflectionException
     */
    public function test_it_should_return_a_tree_view(): void
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
            path: $route->path,
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

    /**
     * @throws PersistenceException
     * @throws ReflectionException
     * @throws RouteNotFoundException
     */
    public function test_it_should_create_entry_group(): void
    {
        $userActor = UserFixture::create(persist: true);
        $this->actAs($userActor);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_STORE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $entryGroupName = $this->faker->word();

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $route->path,
            body: [
                EntryGroupRepository::FIELD_NAME => $entryGroupName,
            ],
        );

        $this->assertEquals(HttpStatusCode::CREATED, $httpResponse->getStatusCode());

        $this->assertDatabaseHas(EntryGroupFixture::getTableName(), [
            EntryGroupFixture::NAME => $entryGroupName,
        ]);
    }

    /**
     * @throws PersistenceException
     * @throws ReflectionException
     * @throws RouteNotFoundException
     */
    public function test_it_should_create_tree_of_entry_groups(): void
    {
        $userActor = UserFixture::create(persist: true);
        $this->actAs($userActor);

        $routeNameCreate = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_STORE);
        $routeStore = Router::getInstance()->getRouteByName($routeNameCreate);
        $this->assertNotNull($routeStore, "Route $routeNameCreate not found");
        $httpMethodCreate = $routeStore->methods[0] ?? null;
        $this->assertNotNull($httpMethodCreate, "Method not found for route $routeNameCreate");

        $routeNameSearch = $this->buildRouteName($this->routePath, EntryGroupController::ACTION_SEARCH);
        $routeSearch = Router::getInstance()->getRouteByName($routeNameSearch);
        $this->assertNotNull($routeSearch, "Route $routeNameSearch not found");
        $httpMethodSearch = $routeSearch->methods[0] ?? null;
        $this->assertNotNull($httpMethodSearch, "Method not found for route $routeNameSearch");

        $routeNameIndex = $this->buildRouteName($this->routePath, EntryGroupController::ACTION_INDEX);
        $routeIndex = Router::getInstance()->getRouteByName($routeNameIndex);
        $this->assertNotNull($routeIndex, "Route $routeNameIndex not found");
        $httpMethodIndex = $routeIndex->methods[0] ?? null;
        $this->assertNotNull($httpMethodIndex, "Method not found for route $routeNameIndex");

        $deep = 3;

        $names = [];
        $parentId = null;

        $entryGroupBase = $this->faker->word();
        while ($deep-- > 0) {
            $entryGroupName = "$entryGroupBase-$deep";
            $names[] = $entryGroupName;
            $httpResponse = $this->sendRequest(
                method: $httpMethodCreate,
                uri: $routeStore->path,
                body: [
                    EntryGroupRepository::FIELD_NAME => $entryGroupName,
                    EntryGroupRepository::FIELD_PARENT_ENTRY_GROUP_ID => $parentId,
                ],
            );

            $this->assertEquals(HttpStatusCode::CREATED, $httpResponse->getStatusCode(), $httpResponse->getContent());
            $httpResponseSearch = $this->sendRequest(
                method: $httpMethodSearch,
                uri: $routeSearch->path,
                query: [
                    EntryGroupController::FIELD_QUERY => $entryGroupName,
                ],
            );

            $this->assertEquals(HttpStatusCode::OK, $httpResponseSearch->getStatusCode(), $httpResponseSearch->getContent());

            $response = json_decode($httpResponseSearch->getContent(), true);
            $this->assertIsArray($response);
            $this->assertCount(1, $response, $httpResponseSearch->getContent());
            $this->assertArrayHasKey(EntryGroupRepository::FIELD_ID, $response[0]);
            $parentId = $response[0][EntryGroupRepository::FIELD_ID];
        }

        $httpResponseIndex = $this->sendRequest(
            method: $httpMethodIndex,
            uri: $routeIndex->path,
        );

        $this->assertEquals(HttpStatusCode::OK, $httpResponseIndex->getStatusCode(), $httpResponseIndex->getContent());
        $response = json_decode($httpResponseIndex->getContent(), true);
        $this->assertArrayHasKey('trees', $response);
        $trees = $response['trees'];
        $this->assertNotEmpty($trees);

        foreach ($trees as $tree) {

            $pool = [$tree];
            while ($child = array_shift($pool)) {
                $this->assertArrayHasKey(EntryGroupTreeNode::FIELD_ENTRY_GROUP, $child, $httpResponseIndex->getContent());
                $this->assertArrayHasKey(EntryGroupTreeNode::FIELD_CHILDREN, $child, $httpResponseIndex->getContent());

                /**
                 * @var array<string, mixed> $entryGroup
                 */
                $entryGroup = $child[EntryGroupTreeNode::FIELD_ENTRY_GROUP];
                $this->assertIsArray($entryGroup, $httpResponseIndex->getContent());
                $this->assertArrayHasKey(EntryGroupRepository::FIELD_ID, $entryGroup, $httpResponseIndex->getContent());
                $this->assertArrayHasKey(EntryGroupRepository::FIELD_NAME, $entryGroup, $httpResponseIndex->getContent());
                $this->assertContains(
                    $entryGroup[EntryGroupRepository::FIELD_NAME],
                    $names,
                    "{$entryGroup[EntryGroupRepository::FIELD_NAME]} does not exist in " . implode(', ', $names),
                );
                unset($names[array_search($entryGroup[EntryGroupRepository::FIELD_NAME], $names)]);

                $pool += $child[EntryGroupTreeNode::FIELD_CHILDREN];
            }

        }
        $this->assertEmpty($names, "Not all names were found: " . implode(', ', $names));

    }
}
