<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Functional\Infrastructure\Http\Controller;

use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\Identity\Infrastructure\Http\Controller\UserController;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Entity\EntryGroupUser;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Enum\RoleEnum;
use App\Module\PasswordBroker\Infrastructure\EntryGroupUser\Repository\EntryGroupUserRepository;
use App\Module\PasswordBroker\Infrastructure\Http\Route\EntryGroupUserIndexRoute;
use App\Module\PasswordBroker\Infrastructure\Http\Route\EntryGroupUserRoute;
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

class EntryGroupUserControllerTest extends FunctionalTestCase
{
    private array $routePath = [
        AppRoute::GROUP_NAME,
        PasswordBrokerRoute::GROUP_NAME,
        EntryGroupUserRoute::GROUP_NAME,
    ];
    private array $routeIndexPath = [
        AppRoute::GROUP_NAME,
        PasswordBrokerRoute::GROUP_NAME,
        EntryGroupUserRoute::GROUP_NAME,
        EntryGroupUserIndexRoute::GROUP_NAME,
    ];

    /**
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     * @throws ReflectionException
     * @throws RouteNotFoundException
     */
    public function test_it_should_return_list_of_users_in_group(): void
    {
        $usersInGroupNum = $this->faker->numberBetween(2, 7);
        $usersInGroup = UserFixture::createMany($usersInGroupNum, persist: true);
        EntryGroupUserFixture::createMany(count: 2); // other users and group
        $userActor = UserFixture::create(attributes: [UserFixture::IS_ADMIN => true], persist: true);
        $this->actAs($userActor);
        $entryGroup = EntryGroupFixture::create(persist: true);

        $usersInGroupIds = [];
        foreach ($usersInGroup as $user) {
            EntryGroupUserFixture::create(
                attributes: [
                    EntryGroupUserFixture::USER_ID => $user->id->toRaw(),
                    EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroup->id->toRaw(),
                ],
                persist: true,
            );
            $usersInGroupIds[] = $user->id->toRaw();
        }

        $routeName = $this->buildRouteName($this->routeIndexPath, RestControllerInterface::ACTION_INDEX);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            filterParams: [EntryGroupUserRepository::FIELD_ENTRY_GROUP_ID => $entryGroup->id->toRaw()],
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
        $this->assertCount($usersInGroupNum, $response['data']);
        $this->assertArrayHasKey('total', $response['pagination']);
        $this->assertEquals($usersInGroupNum, $response['pagination']['total']);

        foreach ($response['data'] as $user) {
            $this->assertArrayHasKey('id', $user);
            $this->assertArrayHasKey('entryGroupId', $user);
            $this->assertArrayHasKey('userId', $user);
            $this->assertArrayHasKey('role', $user);
            $this->assertArrayHasKey('createdAt', $user);
            $this->assertArrayHasKey('updatedAt', $user);

            $this->assertContains($user['userId'], $usersInGroupIds);
            unset($usersInGroupIds[array_search($user['userId'], $usersInGroupIds)]);
        }

        $this->assertEmpty($usersInGroupIds, "Not all userIds were found: " . implode(', ', $usersInGroupIds));
    }

    /**
     * @throws PersistenceException
     * @throws ReflectionException
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_return_list_of_user_groups(): void
    {
        $userActor = UserFixture::create(attributes: [UserFixture::IS_ADMIN => true], persist: true);
        $this->actAs($userActor);

        $user = UserFixture::create(persist: true);
        $userGroupNum = $this->faker->numberBetween(2, 7);
        $userGroups = EntryGroupUserFixture::createMany(count: $userGroupNum, attributes: [EntryGroupUserFixture::USER_ID => $user->id->toRaw()]);
        $userGroupIds = array_map(fn(EntryGroupUser $userGroup) => $userGroup->entryGroupId->toRaw(), $userGroups);

        $routeName = $this->buildRouteName($this->routeIndexPath, RestControllerInterface::ACTION_INDEX);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            filterParams: [EntryGroupUserRepository::FIELD_USER_ID => $user->id->toRaw()],
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
        $this->assertCount($userGroupNum, $response['data']);
        $this->assertArrayHasKey('total', $response['pagination']);
        $this->assertEquals($userGroupNum, $response['pagination']['total']);

        foreach ($response['data'] as $user) {
            $this->assertArrayHasKey('id', $user);
            $this->assertArrayHasKey('entryGroupId', $user);
            $this->assertArrayHasKey('userId', $user);
            $this->assertArrayHasKey('role', $user);
            $this->assertArrayHasKey('createdAt', $user);
            $this->assertArrayHasKey('updatedAt', $user);

            $this->assertContains($user['entryGroupId'], $userGroupIds);
            unset($userGroupIds[array_search($user['entryGroupId'], $userGroupIds)]);
        }

        $this->assertEmpty($userGroupIds, "Not all groupIds were found: " . implode(', ', $userGroupIds));
    }

    /**
     * @throws PersistenceException
     * @throws ReflectionException
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_return_403_instead_of_list_for_non_admin_user(): void
    {
        $user = UserFixture::create(attributes: [UserFixture::IS_ADMIN => false], persist: true);
        $this->actAs($user);

        $routeName = $this->buildRouteName($this->routeIndexPath, RestControllerInterface::ACTION_INDEX);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
        );

        $httpResponse = $this->sendRequest($httpMethod, $uri);

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());
    }

    /**
     * @throws PersistenceException
     * @throws ReflectionException
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     */
    public function test_admin_can_add_anyone_to_group(): void
    {
        $userActor = UserFixture::create(persist: true);
        $userAdmin = UserFixture::create(persist: true);
        $userModerator = UserFixture::create(persist: true);
        $userMember = UserFixture::create(persist: true);

        $this->actAs($userActor);

        $entryGroupUser = EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $userActor->id->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::ADMIN->value,
            ],
            persist: true,
        );

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_STORE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: [
                EntryGroupUserRepository::FIELD_USER_ID => $userAdmin->id->toRaw(),
                EntryGroupUserRepository::FIELD_ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
                EntryGroupUserRepository::FIELD_ROLE => RoleEnum::ADMIN->value,
                UserController::FIELD_MASTER_PASSWORD => UserFixture::DEFAULT_MASTER_PASSWORD,
            ],
        );

        $this->assertEquals(HttpStatusCode::CREATED, $httpResponse->getStatusCode());

        $this->assertDatabaseHas(EntryGroupUserFixture::getTableName(), [
            EntryGroupUserFixture::USER_ID => $userAdmin->id->toRaw(),
            EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
            EntryGroupUserFixture::ROLE => RoleEnum::ADMIN->value,
        ]);

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: [
                EntryGroupUserRepository::FIELD_USER_ID => $userModerator->id->toRaw(),
                EntryGroupUserRepository::FIELD_ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
                EntryGroupUserRepository::FIELD_ROLE => RoleEnum::MODERATOR->value,
                UserController::FIELD_MASTER_PASSWORD => UserFixture::DEFAULT_MASTER_PASSWORD,
            ],
        );

        $this->assertEquals(HttpStatusCode::CREATED, $httpResponse->getStatusCode());

        $this->assertDatabaseHas(EntryGroupUserFixture::getTableName(), [
            EntryGroupUserFixture::USER_ID => $userModerator->id->toRaw(),
            EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
            EntryGroupUserFixture::ROLE => RoleEnum::MODERATOR->value,
        ]);

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: [
                EntryGroupUserRepository::FIELD_USER_ID => $userMember->id->toRaw(),
                EntryGroupUserRepository::FIELD_ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
                EntryGroupUserRepository::FIELD_ROLE => RoleEnum::MEMBER->value,
                UserController::FIELD_MASTER_PASSWORD => UserFixture::DEFAULT_MASTER_PASSWORD,
            ],
        );

        $this->assertEquals(HttpStatusCode::CREATED, $httpResponse->getStatusCode());

        $this->assertDatabaseHas(EntryGroupUserFixture::getTableName(), [
            EntryGroupUserFixture::USER_ID => $userMember->id->toRaw(),
            EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
            EntryGroupUserFixture::ROLE => RoleEnum::MEMBER->value,
        ]);
    }

    /**
     * @throws PersistenceException
     * @throws ReflectionException
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     */
    public function test_moderator_can_add_member_to_group(): void
    {
        $userActor = UserFixture::create(persist: true);
        $user = UserFixture::create(persist: true);

        $this->actAs($userActor);

        $entryGroupUser = EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $userActor->id->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::MODERATOR->value,
            ],
            persist: true,
        );

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_STORE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: [
                EntryGroupUserRepository::FIELD_USER_ID => $user->id->toRaw(),
                EntryGroupUserRepository::FIELD_ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
                EntryGroupUserRepository::FIELD_ROLE => RoleEnum::MEMBER->value,
                UserController::FIELD_MASTER_PASSWORD => UserFixture::DEFAULT_MASTER_PASSWORD,
            ],
        );

        $this->assertEquals(HttpStatusCode::CREATED, $httpResponse->getStatusCode());

        $this->assertDatabaseHas(EntryGroupUserFixture::getTableName(), [
            EntryGroupUserFixture::USER_ID => $user->id->toRaw(),
            EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
            EntryGroupUserFixture::ROLE => RoleEnum::MEMBER->value,
        ]);
    }


    /**
     * @throws PersistenceException
     * @throws ReflectionException
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_return_403_instead_of_adding_user_to_group_for_non_admin_or_non_moderator_user(): void
    {
        $userActor = UserFixture::create(attributes: [UserFixture::IS_ADMIN => false], persist: true);
        $user = UserFixture::create(persist: true);
        $this->actAs($userActor);
        $entryGroupUser = EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $userActor->id->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::MEMBER->value,
            ],
            persist: true,
        );

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_STORE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: [
                EntryGroupUserRepository::FIELD_USER_ID => $user->id->toRaw(),
                EntryGroupUserRepository::FIELD_ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
                EntryGroupUserRepository::FIELD_ROLE => RoleEnum::MEMBER->value,
                UserController::FIELD_MASTER_PASSWORD => UserFixture::DEFAULT_MASTER_PASSWORD,
            ],
        );

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());

        $this->assertDatabaseMissing(EntryGroupUserFixture::getTableName(), [
            EntryGroupUserFixture::USER_ID => $user->id->toRaw(),
            EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
            EntryGroupUserFixture::ROLE => RoleEnum::MEMBER->value,
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     * @throws PersistenceException
     */
    public function test_it_should_return_403_instead_of_adding_moderator_or_admin_to_group_for_moderator_user(): void
    {
        $userActor = UserFixture::create(attributes: [UserFixture::IS_ADMIN => false], persist: true);
        $user = UserFixture::create(persist: true);
        $this->actAs($userActor);
        $entryGroupUser = EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $userActor->id->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::MODERATOR->value,
            ],
            persist: true,
        );

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_STORE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: [
                EntryGroupUserRepository::FIELD_USER_ID => $user->id->toRaw(),
                EntryGroupUserRepository::FIELD_ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
                EntryGroupUserRepository::FIELD_ROLE => RoleEnum::MODERATOR->value,
                UserController::FIELD_MASTER_PASSWORD => UserFixture::DEFAULT_MASTER_PASSWORD,
            ],
        );

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());

        $this->assertDatabaseMissing(EntryGroupUserFixture::getTableName(), [
            EntryGroupUserFixture::USER_ID => $user->id->toRaw(),
            EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
            EntryGroupUserFixture::ROLE => RoleEnum::MODERATOR->value,
        ]);

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: [
                EntryGroupUserRepository::FIELD_USER_ID => $user->id->toRaw(),
                EntryGroupUserRepository::FIELD_ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
                EntryGroupUserRepository::FIELD_ROLE => RoleEnum::ADMIN->value,
                UserController::FIELD_MASTER_PASSWORD => UserFixture::DEFAULT_MASTER_PASSWORD,
            ],
        );

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());

        $this->assertDatabaseMissing(EntryGroupUserFixture::getTableName(), [
            EntryGroupUserFixture::USER_ID => $user->id->toRaw(),
            EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
            EntryGroupUserFixture::ROLE => RoleEnum::ADMIN->value,
        ]);

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: [
                EntryGroupUserRepository::FIELD_USER_ID => $user->id->toRaw(),
                EntryGroupUserRepository::FIELD_ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
                EntryGroupUserRepository::FIELD_ROLE => RoleEnum::MEMBER->value,
                UserController::FIELD_MASTER_PASSWORD => UserFixture::DEFAULT_MASTER_PASSWORD,
            ],
        );

        $this->assertEquals(HttpStatusCode::CREATED, $httpResponse->getStatusCode());

        $this->assertDatabaseHas(EntryGroupUserFixture::getTableName(), [
            EntryGroupUserFixture::USER_ID => $user->id->toRaw(),
            EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUser->entryGroupId->toRaw(),
            EntryGroupUserFixture::ROLE => RoleEnum::MEMBER->value,
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     * @throws PersistenceException
     */
    public function test_it_should_show_user_group_by_id(): void
    {
        $userActor = UserFixture::create(persist: true);
        $this->actAs($userActor);

        $entryGroupUser = EntryGroupUserFixture::create(persist: true);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_SHOW);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupUserRoute::PARAM_ENTRY_GROUP_USER_ID => $entryGroupUser->id->toRaw(),
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
        $this->assertArrayHasKey(EntryGroupUserRepository::FIELD_ID, $response);
        $this->assertArrayHasKey(EntryGroupUserRepository::FIELD_USER_ID, $response);
        $this->assertArrayHasKey(EntryGroupUserRepository::FIELD_ENTRY_GROUP_ID, $response);
        $this->assertArrayHasKey(EntryGroupUserRepository::FIELD_ROLE, $response);
        $this->assertArrayHasKey(EntryGroupUserRepository::FIELD_CREATED_AT, $response);
        $this->assertArrayHasKey(EntryGroupUserRepository::FIELD_UPDATED_AT, $response);

        $this->assertEquals($entryGroupUser->id->toRaw(), $response[EntryGroupUserRepository::FIELD_ID]);
        $this->assertEquals($entryGroupUser->userId->toRaw(), $response[EntryGroupUserRepository::FIELD_USER_ID]);
        $this->assertEquals($entryGroupUser->entryGroupId->toRaw(), $response[EntryGroupUserRepository::FIELD_ENTRY_GROUP_ID]);
        $this->assertEquals($entryGroupUser->role->toRaw(), $response[EntryGroupUserRepository::FIELD_ROLE]);
        $this->assertEquals($entryGroupUser->createdAt->toRaw(), $response[EntryGroupUserRepository::FIELD_CREATED_AT]);
        $this->assertEquals($entryGroupUser->updatedAt->toRaw(), $response[EntryGroupUserRepository::FIELD_UPDATED_AT]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     * @throws PersistenceException
     */
    public function test_admin_can_change_role_of_user_in_group(): void
    {
        $userActor = UserFixture::create(attributes: [UserFixture::IS_ADMIN => false], persist: true);
        $user = UserFixture::create(persist: true);
        $this->actAs($userActor);
        $entryGroupUserOwner = EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $userActor->id->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::ADMIN->value,
            ],
            persist: true,
        );

        $entryGroupUserTarget = EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $user->id->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUserOwner->entryGroupId->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::MEMBER->value,
            ],
            persist: true,
        );

        $this->assertDatabaseHas(EntryGroupUserFixture::getTableName(), [
            EntryGroupUserFixture::USER_ID => $user->id->toRaw(),
            EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUserOwner->entryGroupId->toRaw(),
            EntryGroupUserFixture::ROLE => RoleEnum::MEMBER->value,
        ]);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_UPDATE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupUserRoute::PARAM_ENTRY_GROUP_USER_ID => $entryGroupUserTarget->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: [
                EntryGroupUserRepository::FIELD_ROLE => RoleEnum::MODERATOR->value,
            ],
        );

        $this->assertEquals(HttpStatusCode::NO_CONTENT, $httpResponse->getStatusCode());

        $this->assertDatabaseHas(EntryGroupUserFixture::getTableName(), [
            EntryGroupUserFixture::USER_ID => $user->id->toRaw(),
            EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUserOwner->entryGroupId->toRaw(),
            EntryGroupUserFixture::ROLE => RoleEnum::MODERATOR->value,
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     * @throws PersistenceException
     */
    public function test_moderator_can_not_change_role_of_user_in_group(): void
    {
        $userActor = UserFixture::create(attributes: [UserFixture::IS_ADMIN => false], persist: true);
        $user = UserFixture::create(persist: true);
        $this->actAs($userActor);
        $entryGroupUserOwner = EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $userActor->id->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::MODERATOR->value,
            ],
            persist: true,
        );

        $entryGroupUserTarget = EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $user->id->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUserOwner->entryGroupId->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::MEMBER->value,
            ],
            persist: true,
        );

        $this->assertDatabaseHas(EntryGroupUserFixture::getTableName(), [
            EntryGroupUserFixture::USER_ID => $user->id->toRaw(),
            EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUserOwner->entryGroupId->toRaw(),
            EntryGroupUserFixture::ROLE => RoleEnum::MEMBER->value,
        ]);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_UPDATE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupUserRoute::PARAM_ENTRY_GROUP_USER_ID => $entryGroupUserTarget->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: [
                EntryGroupUserRepository::FIELD_ROLE => RoleEnum::MODERATOR->value,
            ],
        );

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());

        $this->assertDatabaseHas(EntryGroupUserFixture::getTableName(), [
            EntryGroupUserFixture::USER_ID => $user->id->toRaw(),
            EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUserOwner->entryGroupId->toRaw(),
            EntryGroupUserFixture::ROLE => RoleEnum::MEMBER->value,
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws ReflectionException
     * @throws PersistenceException
     */
    public function test_member_can_not_change_role_of_user_in_group(): void
    {
        $userActor = UserFixture::create(attributes: [UserFixture::IS_ADMIN => false], persist: true);
        $user = UserFixture::create(persist: true);
        $this->actAs($userActor);
        $entryGroupUserOwner = EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $userActor->id->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::MEMBER->value,
            ],
            persist: true,
        );

        $entryGroupUserTarget = EntryGroupUserFixture::create(
            attributes: [
                EntryGroupUserFixture::USER_ID => $user->id->toRaw(),
                EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUserOwner->entryGroupId->toRaw(),
                EntryGroupUserFixture::ROLE => RoleEnum::MEMBER->value,
            ],
            persist: true,
        );

        $this->assertDatabaseHas(EntryGroupUserFixture::getTableName(), [
            EntryGroupUserFixture::USER_ID => $user->id->toRaw(),
            EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUserOwner->entryGroupId->toRaw(),
            EntryGroupUserFixture::ROLE => RoleEnum::MEMBER->value,
        ]);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_UPDATE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupUserRoute::PARAM_ENTRY_GROUP_USER_ID => $entryGroupUserTarget->id->toRaw(),
            ],
        );

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: [
                EntryGroupUserRepository::FIELD_ROLE => RoleEnum::MODERATOR->value,
            ],
        );

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());

        $this->assertDatabaseHas(EntryGroupUserFixture::getTableName(), [
            EntryGroupUserFixture::USER_ID => $user->id->toRaw(),
            EntryGroupUserFixture::ENTRY_GROUP_ID => $entryGroupUserOwner->entryGroupId->toRaw(),
            EntryGroupUserFixture::ROLE => RoleEnum::MEMBER->value,
        ]);
    }
}
