<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Functional\Infrastructure\Http\Controller;

use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\PasswordBroker\Application\EntryGroup\DTO\EntryGroupTreeResponse;
use App\Module\PasswordBroker\Domain\EntryGroup\DTO\EntryGroupTreeNode;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupName;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Enum\RoleEnum;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\Repository\EntryGroupRepository;
use App\Module\PasswordBroker\Infrastructure\Http\Controller\EntryGroupController;
use App\Module\PasswordBroker\Infrastructure\Http\Route\EntryGroupAdminRoute;
use App\Module\PasswordBroker\Infrastructure\Http\Route\EntryGroupModeratorRoute;
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
use Tests\Module\PasswordBroker\Fixture\EntryGroupUserFixture;
use Tests\Shared\FunctionalTestCase;

class EntryGroupControllerTest extends FunctionalTestCase
{
    private array $routePath;
    private array $routeAdminPath;
    private array $routeModeratorPath;

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
        $this->routeAdminPath = [
            AppRoute::GROUP_NAME,
            PasswordBrokerRoute::GROUP_NAME,
            EntryGroupRoute::GROUP_NAME,
            EntryGroupAdminRoute::GROUP_NAME,
        ];
        $this->routeModeratorPath = [
            AppRoute::GROUP_NAME,
            PasswordBrokerRoute::GROUP_NAME,
            EntryGroupRoute::GROUP_NAME,
            EntryGroupModeratorRoute::GROUP_NAME,
        ];
    }

    /**
     * @throws PersistenceException
     * @throws ReflectionException
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
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
     * @throws RsaDomainServiceException
     */
    public function test_it_should_create_entry_group(): void
    {
        $userActor = UserFixture::create(persist: true);
        $this->actAs($userActor);

        $routeName = $this->buildRouteName($this->routeAdminPath, RestControllerInterface::ACTION_STORE);
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
     * @throws RsaDomainServiceException
     */
    public function test_it_should_create_tree_of_entry_groups(): void
    {
        $userActor = UserFixture::create(persist: true);
        $this->actAs($userActor);

        $routeNameCreate = $this->buildRouteName($this->routeAdminPath, RestControllerInterface::ACTION_STORE);
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

    /**
     * @throws PersistenceException
     * @throws ReflectionException
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_return_users_in_group(): void
    {
        $userInGroupMember1 = UserFixture::create(persist: true);
        $userInGroupMember2 = UserFixture::create(persist: true);
        $userInGroupModerator = UserFixture::create(persist: true);
        $userInGroupAdmin = UserFixture::create(persist: true);
        $userActor = UserFixture::create(persist: true);
        $this->actAs($userActor);

        $userIds = [
            $userInGroupMember1->id->toRaw(),
            $userInGroupMember2->id->toRaw(),
            $userInGroupModerator->id->toRaw(),
            $userInGroupAdmin->id->toRaw(),
            $userActor->id->toRaw(),
        ];

        $entryGroup = EntryGroupFixture::create(persist: true);

        EntryGroupUserFixture::create(attributes: [
            EntryGroupUserFixture::USER_ID => $userInGroupMember1->id->toRaw(),
            EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            EntryGroupUserFixture::ROLE => RoleEnum::MEMBER,
        ], persist: true);

        EntryGroupUserFixture::create(attributes: [
            EntryGroupUserFixture::USER_ID => $userInGroupMember2->id->toRaw(),
            EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            EntryGroupUserFixture::ROLE => RoleEnum::MEMBER,
        ], persist: true);

        EntryGroupUserFixture::create(attributes: [
            EntryGroupUserFixture::USER_ID => $userInGroupModerator->id->toRaw(),
            EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            EntryGroupUserFixture::ROLE => RoleEnum::MODERATOR,
        ], persist: true);

        EntryGroupUserFixture::create(attributes: [
            EntryGroupUserFixture::USER_ID => $userInGroupAdmin->id->toRaw(),
            EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            EntryGroupUserFixture::ROLE => RoleEnum::ADMIN,
        ], persist: true);

        EntryGroupUserFixture::create(attributes: [
            EntryGroupUserFixture::USER_ID => $userActor->id->toRaw(),
            EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            EntryGroupUserFixture::ROLE => RoleEnum::MODERATOR,
        ], persist: true);

        $routeName = $this->buildRouteName($this->routeModeratorPath, EntryGroupController::ACTION_USERS_IN_GROUP);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupModeratorRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
        );

        $this->assertEquals(HttpStatusCode::OK, $httpResponse->getStatusCode());

        $responseData = json_decode($httpResponse->getContent(), true);

        $this->assertIsArray($responseData);
        $this->assertNotEmpty($responseData);
        $this->assertArrayHasKey('pagination', $responseData);
        $this->assertIsArray($responseData['pagination']);
        $this->assertArrayHasKey('total', $responseData['pagination']);
        $this->assertEquals(count($userIds), $responseData['pagination']['total']);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertIsArray($responseData['data']);
        $this->assertNotEmpty($responseData['data']);
        $this->assertCount(count($userIds), $responseData['data']);

        foreach ($responseData['data'] as $user) {
            $this->assertArrayHasKey('id', $user);
            $this->assertArrayHasKey('entryGroupId', $user);
            $this->assertArrayHasKey('userId', $user);
            $this->assertArrayHasKey('role', $user);
            $this->assertArrayHasKey('createdAt', $user);
            $this->assertArrayHasKey('updatedAt', $user);
            $this->assertContains(
                $user['userId'],
                $userIds,
                "User with id {$user['id']} does not exist in " . implode(', ', $userIds),
            );
            unset($userIds[array_search($user['userId'], $userIds)]);
        }

        $this->assertEmpty($userIds, "Not all userIds were found: " . implode(', ', $userIds));
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     * @throws PersistenceException
     */
    public function test_it_should_show_entry_group(): void
    {
        $userActor = UserFixture::create(persist: true);
        $this->actAs($userActor);

        $entryGroup = EntryGroupFixture::create(persist: true);
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $userActor->id->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            ],
            persist: true,
        );

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_SHOW);
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
        $this->assertArrayHasKey(EntryGroupRepository::FIELD_ID, $response);
        $this->assertEquals($entryGroup->id->toRaw(), $response[EntryGroupRepository::FIELD_ID]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     * @throws PersistenceException
     */
    public function test_show_entry_group_should_return_403_for_user_not_in_group(): void
    {
        $userActor = UserFixture::create(persist: true);
        $this->actAs($userActor);

        $entryGroup = EntryGroupFixture::create(persist: true);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_SHOW);
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
        $content = $httpResponse->getContent();
        $this->assertJson($content);
        $response = json_decode($content, true);
        $this->assertArrayHasKey('error', $response);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     * @throws PersistenceException
     */
    public function test_admin_should_be_able_to_rename_entry_group(): void
    {
        $userActor = UserFixture::create(persist: true);
        $this->actAs($userActor);

        $entryGroup = EntryGroupFixture::create(persist: true);
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $userActor->id->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::ADMIN,
            ],
            persist: true,
        );

        $entryGroupOldName = $entryGroup->name;
        $entryGroup->name = EntryGroupName::fromRaw($this->faker->word());

        $routeName = $this->buildRouteName($this->routeAdminPath, RestControllerInterface::ACTION_UPDATE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");


        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupAdminRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: $entryGroup->getAsArray(),
        );

        $this->assertEquals(HttpStatusCode::NO_CONTENT, $httpResponse->getStatusCode());
        $this->assertDatabaseHas(EntryGroupFixture::getTableName(), [
            EntryGroupFixture::ID => $entryGroup->id->toRaw(),
            EntryGroupFixture::NAME => $entryGroup->name->toRaw(),
        ]);
        $this->assertDatabaseMissing(EntryGroupFixture::getTableName(), [
            EntryGroupFixture::ID => $entryGroup->id->toRaw(),
            EntryGroupFixture::NAME => $entryGroupOldName->toRaw(),
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     * @throws PersistenceException
     */
    public function test_member_should_be_unable_to_rename_entry_group(): void
    {
        $userActor = UserFixture::create(persist: true);
        $this->actAs($userActor);

        $entryGroup = EntryGroupFixture::create(persist: true);
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $userActor->id->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::MEMBER,
            ],
            persist: true,
        );
        $entryGroupOldName = $entryGroup->name;
        $entryGroup->name = EntryGroupName::fromRaw($this->faker->word());

        $routeName = $this->buildRouteName($this->routeAdminPath, RestControllerInterface::ACTION_UPDATE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");


        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupAdminRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: $entryGroup->getAsArray(),
        );

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());
        $this->assertDatabaseHas(EntryGroupFixture::getTableName(), [
            EntryGroupFixture::ID => $entryGroup->id->toRaw(),
            EntryGroupFixture::NAME => $entryGroupOldName->toRaw(),
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     * @throws PersistenceException
     */
    public function test_moderator_should_be_unable_to_rename_entry_group(): void
    {
        $userActor = UserFixture::create(persist: true);
        $this->actAs($userActor);

        $entryGroup = EntryGroupFixture::create(persist: true);
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $userActor->id->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::MODERATOR,
            ],
            persist: true,
        );
        $entryGroupOldName = $entryGroup->name;
        $entryGroup->name = EntryGroupName::fromRaw($this->faker->word());

        $routeName = $this->buildRouteName($this->routeAdminPath, RestControllerInterface::ACTION_UPDATE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");


        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupAdminRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: $entryGroup->getAsArray(),
        );

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());
        $this->assertDatabaseHas(EntryGroupFixture::getTableName(), [
            EntryGroupFixture::ID => $entryGroup->id->toRaw(),
            EntryGroupFixture::NAME => $entryGroupOldName->toRaw(),
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     * @throws PersistenceException
     */
    public function test_not_in_group_user_should_be_unable_to_rename_entry_group(): void
    {
        $userActor = UserFixture::create(persist: true);
        $this->actAs($userActor);

        $entryGroup = EntryGroupFixture::create(persist: true);
        $entryGroupOldName = $entryGroup->name;
        $entryGroup->name = EntryGroupName::fromRaw($this->faker->word());

        $routeName = $this->buildRouteName($this->routeAdminPath, RestControllerInterface::ACTION_UPDATE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");


        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupAdminRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: $entryGroup->getAsArray(),
        );

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());
        $this->assertDatabaseHas(EntryGroupFixture::getTableName(), [
            EntryGroupFixture::ID => $entryGroup->id->toRaw(),
            EntryGroupFixture::NAME => $entryGroupOldName->toRaw(),
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     * @throws PersistenceException
     */
    public function test_admin_should_be_able_to_soft_delete_entry_group(): void
    {
        $userActor = UserFixture::create(persist: true);
        $this->actAs($userActor);

        $entryGroup = EntryGroupFixture::create(persist: true);
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $userActor->id->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::ADMIN,
            ],
            persist: true,
        );

        $routeName = $this->buildRouteName($this->routeAdminPath, RestControllerInterface::ACTION_DESTROY);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");


        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupAdminRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
        );

        $this->assertEquals(HttpStatusCode::NO_CONTENT, $httpResponse->getStatusCode());

        $this->assertDatabaseMissing(EntryGroupFixture::getTableName(), [
            EntryGroupFixture::ID => $entryGroup->id->toRaw(),
            EntryGroupFixture::DELETED_AT => null,
        ]);

        $this->assertDatabaseHas(EntryGroupFixture::getTableName(), [
            EntryGroupFixture::ID => $entryGroup->id->toRaw(),
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     * @throws PersistenceException
     */
    public function test_member_should_be_unable_to_soft_delete_entry_group(): void
    {
        $userActor = UserFixture::create(persist: true);
        $this->actAs($userActor);

        $entryGroup = EntryGroupFixture::create(persist: true);
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $userActor->id->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::MEMBER,
            ],
            persist: true,
        );

        $routeName = $this->buildRouteName($this->routeAdminPath, RestControllerInterface::ACTION_DESTROY);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");


        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupAdminRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
        );

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());

        $this->assertDatabaseHas(EntryGroupFixture::getTableName(), [
            EntryGroupFixture::ID => $entryGroup->id->toRaw(),
            EntryGroupFixture::DELETED_AT => null,
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     * @throws PersistenceException
     */
    public function test_moderator_should_be_unable_to_soft_delete_entry_group(): void
    {
        $userActor = UserFixture::create(persist: true);
        $this->actAs($userActor);

        $entryGroup = EntryGroupFixture::create(persist: true);
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $userActor->id->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::MODERATOR,
            ],
            persist: true,
        );

        $routeName = $this->buildRouteName($this->routeAdminPath, RestControllerInterface::ACTION_DESTROY);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");


        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupAdminRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
        );

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());

        $this->assertDatabaseHas(EntryGroupFixture::getTableName(), [
            EntryGroupFixture::ID => $entryGroup->id->toRaw(),
            EntryGroupFixture::DELETED_AT => null,
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     * @throws PersistenceException
     */
    public function test_not_in_group_user_should_be_unable_to_soft_delete_entry_group(): void
    {
        $userActor = UserFixture::create(persist: true);
        $this->actAs($userActor);

        $entryGroup = EntryGroupFixture::create(persist: true);

        $routeName = $this->buildRouteName($this->routeAdminPath, RestControllerInterface::ACTION_DESTROY);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");


        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupAdminRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
        );

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());

        $this->assertDatabaseHas(EntryGroupFixture::getTableName(), [
            EntryGroupFixture::ID => $entryGroup->id->toRaw(),
            EntryGroupFixture::DELETED_AT => null,
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     * @throws PersistenceException
     */
    public function test_admin_should_be_able_to_move_entry_group_to_root(): void
    {
        $userActor = UserFixture::create(persist: true);
        $this->actAs($userActor);

        $entryGroupSource = EntryGroupFixture::create(persist: true);
        $entryGroup = EntryGroupFixture::create(
            attributes: [
                EntryGroupFixture::PARENT_ENTRY_GROUP_ID => $entryGroupSource->id->toRaw() ,
            ],
            persist: true,
        );
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $userActor->id->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::ADMIN,
            ],
            persist: true,
        );

        $routeName = $this->buildRouteName($this->routeAdminPath, EntryGroupController::ACTION_MOVE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupAdminRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
        );

        $this->assertEquals(HttpStatusCode::NO_CONTENT, $httpResponse->getStatusCode());

        $this->assertDatabaseHas(EntryGroupFixture::getTableName(), [
            EntryGroupFixture::ID => $entryGroup->id->toRaw(),
            EntryGroupFixture::PARENT_ENTRY_GROUP_ID => null,
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     * @throws PersistenceException
     */
    public function test_admin_should_be_able_to_move_entry_group_to_another_group(): void
    {
        $userActor = UserFixture::create(persist: true);
        $this->actAs($userActor);

        $entryGroup = EntryGroupFixture::create(persist: true);
        $entryGroupTarget = EntryGroupFixture::create(persist: true);
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $userActor->id->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::ADMIN,
            ],
            persist: true,
        );
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $userActor->id->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupTarget->id->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::ADMIN,
            ],
            persist: true,
        );

        $routeName = $this->buildRouteName($this->routeAdminPath, EntryGroupController::ACTION_MOVE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");


        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupAdminRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: [
                EntryGroupController::FIELD_TARGET_ENTRY_GROUP_ID => $entryGroupTarget->id->toRaw(),
            ],
        );

        $this->assertEquals(HttpStatusCode::NO_CONTENT, $httpResponse->getStatusCode());
        $this->assertDatabaseHas(EntryGroupFixture::getTableName(), [
            EntryGroupFixture::ID => $entryGroup->id->toRaw(),
            EntryGroupFixture::PARENT_ENTRY_GROUP_ID => $entryGroupTarget->id->toRaw(),
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     * @throws PersistenceException
     */
    public function test_admin_should_be_unable_to_move_entry_group_to_non_their_group(): void
    {
        $userActor = UserFixture::create(persist: true);
        $this->actAs($userActor);

        $entryGroup = EntryGroupFixture::create(persist: true);
        $entryGroupTarget = EntryGroupFixture::create(persist: true);
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $userActor->id->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::ADMIN,
            ],
            persist: true,
        );

        $routeName = $this->buildRouteName($this->routeAdminPath, EntryGroupController::ACTION_MOVE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");


        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupAdminRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: [
                EntryGroupController::FIELD_TARGET_ENTRY_GROUP_ID => $entryGroupTarget->id->toRaw(),
            ],
        );

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());
        $this->assertDatabaseHas(EntryGroupFixture::getTableName(), [
            EntryGroupFixture::ID => $entryGroup->id->toRaw(),
            EntryGroupFixture::PARENT_ENTRY_GROUP_ID => null,
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     * @throws PersistenceException
     */
    public function test_moderator_should_be_unable_to_move_entry_group(): void
    {
        $userActor = UserFixture::create(persist: true);
        $this->actAs($userActor);

        $entryGroupSource = EntryGroupFixture::create(persist: true);
        $entryGroup = EntryGroupFixture::create(
            attributes: [
                EntryGroupFixture::PARENT_ENTRY_GROUP_ID => $entryGroupSource->id->toRaw() ,
            ],
            persist: true,
        );
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $userActor->id->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::MODERATOR,
            ],
            persist: true,
        );

        $routeName = $this->buildRouteName($this->routeAdminPath, EntryGroupController::ACTION_MOVE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupAdminRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
        );

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());

        $this->assertDatabaseHas(EntryGroupFixture::getTableName(), [
            EntryGroupFixture::ID => $entryGroup->id->toRaw(),
            EntryGroupFixture::PARENT_ENTRY_GROUP_ID => $entryGroupSource->id->toRaw(),
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     * @throws PersistenceException
     */
    public function test_member_should_be_unable_to_move_entry_group(): void
    {
        $userActor = UserFixture::create(persist: true);
        $this->actAs($userActor);

        $entryGroupSource = EntryGroupFixture::create(persist: true);
        $entryGroup = EntryGroupFixture::create(
            attributes: [
                EntryGroupFixture::PARENT_ENTRY_GROUP_ID => $entryGroupSource->id->toRaw() ,
            ],
            persist: true,
        );
        EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $userActor->id->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::MODERATOR,
            ],
            persist: true,
        );

        $routeName = $this->buildRouteName($this->routeAdminPath, EntryGroupController::ACTION_MOVE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupAdminRoute::PARAM_ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
        );

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());

        $this->assertDatabaseHas(EntryGroupFixture::getTableName(), [
            EntryGroupFixture::ID => $entryGroup->id->toRaw(),
            EntryGroupFixture::PARENT_ENTRY_GROUP_ID => $entryGroupSource->id->toRaw(),
        ]);
    }
}
