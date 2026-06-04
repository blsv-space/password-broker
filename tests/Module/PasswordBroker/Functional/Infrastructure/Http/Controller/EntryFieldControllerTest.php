<?php

declare(strict_types=1);

namespace PasswordBroker\Functional\Infrastructure\Http\Controller;

use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\Identity\Infrastructure\Http\Controller\UserController;
use App\Module\PasswordBroker\Application\EntryField\DTO\EntryFieldResponse\DecryptedResponse;
use App\Module\PasswordBroker\Domain\Entry\Entity\Entry;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTitle;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Enum\RoleEnum;
use App\Module\PasswordBroker\Infrastructure\Entry\Repository\EntryRepository;
use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use App\Module\PasswordBroker\Infrastructure\Http\Controller\EntryFieldController;
use App\Module\PasswordBroker\Infrastructure\Http\Route\EntryFieldRoute;
use App\Module\PasswordBroker\Infrastructure\Http\Route\EntryGroupRoute;
use App\Module\PasswordBroker\Infrastructure\Http\Route\EntryRoute;
use App\Module\PasswordBroker\Infrastructure\Http\Route\PasswordBrokerRoute;
use App\Shared\Domain\Security\Encryption\Exception\EncryptionException;
use App\Shared\Infrastructure\Http\Route\AppRoute;
use App\Shared\Infrastructure\Security\Encryption\AesEncryptor;
use App\Shared\Infrastructure\Security\Encryption\InitialVectorProvider;
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
    public function test_create_entry_field_should_return_403_for_user_not_in_group(): void
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
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_update_entry_field(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(attributes: [EntryFieldFixture::ENTRY => $entry], persist: true);
        $oldTitle = $entryField->title;
        $entryField->title = EntryFieldTitle::fromRaw('Updated title');

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_UPDATE);
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
            body: [
                ...$entryField->getAsArray(),
                UserController::FIELD_MASTER_PASSWORD => UserFixture::DEFAULT_MASTER_PASSWORD,
            ],
        );

        $this->assertEquals(HttpStatusCode::NO_CONTENT, $httpResponse->getStatusCode());
        $this->assertDatabaseHas(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ENTRY_ID => $entry->id->toRaw(),
            EntryFieldFixture::TITLE => $entryField->title->toRaw(),
        ]);
        $this->assertDatabaseMissing(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ENTRY_ID => $entry->id->toRaw(),
            EntryFieldFixture::TITLE => $oldTitle->toRaw(),
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws PersistenceException
     */
    public function test_update_entry_field_should_return_403_for_user_not_in_group(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        $entry = EntryFixture::create(attributes: [EntryFixture::ENTRY_GROUP => $entryGroup], persist: true);
        $entryField = EntryFieldFixture::create(attributes: [EntryFieldFixture::ENTRY => $entry], persist: true);
        $oldTitle = $entryField->title;
        $entryField->title = EntryFieldTitle::fromRaw('Updated title');

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_UPDATE);
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
            body: [
                ...$entryField->getAsArray(),
                UserController::FIELD_MASTER_PASSWORD => UserFixture::DEFAULT_MASTER_PASSWORD,
            ],
        );

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());
        $this->assertDatabaseMissing(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ENTRY_ID => $entry->id->toRaw(),
            EntryFieldFixture::TITLE => $entryField->title->toRaw(),
        ]);
        $this->assertDatabaseHas(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ENTRY_ID => $entry->id->toRaw(),
            EntryFieldFixture::TITLE => $oldTitle->toRaw(),
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_soft_delete_entry_field(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(attributes: [EntryFieldFixture::ENTRY => $entry], persist: true);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_DESTROY);
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

        $this->assertEquals(HttpStatusCode::NO_CONTENT, $httpResponse->getStatusCode());
        $this->assertDatabaseMissing(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ENTRY_ID => $entry->id->toRaw(),
            EntryFieldFixture::TITLE => $entryField->title->toRaw(),
            EntryFieldFixture::DELETED_AT => null,
        ]);
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
    public function test_soft_delete_entry_field_should_return_403_for_user_not_in_group(): void
    {
        $entryGroup = EntryGroupFixture::create(persist: true);
        $entry = EntryFixture::create(attributes: [EntryFixture::ENTRY_GROUP => $entryGroup], persist: true);
        $entryField = EntryFieldFixture::create(attributes: [EntryFieldFixture::ENTRY => $entry], persist: true);

        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_DESTROY);
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

        $this->assertEquals(HttpStatusCode::FORBIDDEN, $httpResponse->getStatusCode());
        $this->assertDatabaseHas(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ENTRY_ID => $entry->id->toRaw(),
            EntryFieldFixture::TITLE => $entryField->title->toRaw(),
            EntryFieldFixture::DELETED_AT => null,
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     * @throws EncryptionException
     */
    public function test_it_should_decrypt_entry_field(): void
    {
        $entry = $this->createAnEntry();
        $aesEncryptor = AesEncryptor::getInstance();
        $initialVectorProvider = InitialVectorProvider::getInstance();
        $value = $this->faker->text;
        $iv = $initialVectorProvider->getInitialVector();
        $aesEncryptedData = $aesEncryptor->encrypt(
            data: $value,
            password: EntryGroupUserFixture::DEFAULT_AES_PASSWORD,
            iv: $iv,
        );
        $entryField = EntryFieldFixture::create(
            attributes: [
                EntryFieldFixture::ENTRY => $entry,
                EntryFieldFixture::VALUE_ENCRYPTED => $aesEncryptedData->encryptedData,
                EntryFieldFixture::INITIALIZATION_VECTOR => $iv,
                EntryFieldFixture::TAG => $aesEncryptedData->tag,
                EntryFieldFixture::TYPE => EntryFieldTypeEnum::PASSWORD,
            ],
            persist: true,
        );

        $routeName = $this->buildRouteName($this->routePath, EntryFieldController::ACTION_DECRYPT);
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
            body: [
                UserController::FIELD_MASTER_PASSWORD => UserFixture::DEFAULT_MASTER_PASSWORD,
            ],
        );

        $this->assertEquals(HttpStatusCode::OK, $httpResponse->getStatusCode());
        $content = $httpResponse->getContent();
        $this->assertJson($content);
        $response = json_decode($content, true);
        $this->assertArrayHasKey(DecryptedResponse::FIELD_ENTRY_FIELD_ID, $response);
        $this->assertEquals($entryField->id->toRaw(), $response[DecryptedResponse::FIELD_ENTRY_FIELD_ID]);
        $this->assertArrayHasKey(DecryptedResponse::FIELD_DECRYPTED_VALUE, $response);
        $this->assertEquals($value, $response[DecryptedResponse::FIELD_DECRYPTED_VALUE]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_create_entry_field_password(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(
            attributes: [
                EntryFieldFixture::ENTRY => $entry,
                EntryFieldFixture::TYPE => EntryFieldTypeEnum::PASSWORD,
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
            EntryFieldFixture::TYPE => EntryFieldTypeEnum::PASSWORD->value,
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_create_entry_field_link(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(
            attributes: [
                EntryFieldFixture::ENTRY => $entry,
                EntryFieldFixture::TYPE => EntryFieldTypeEnum::LINK,
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
            EntryFieldFixture::TYPE => EntryFieldTypeEnum::LINK->value,
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_create_entry_field_note(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(
            attributes: [
                EntryFieldFixture::ENTRY => $entry,
                EntryFieldFixture::TYPE => EntryFieldTypeEnum::NOTE,
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
            EntryFieldFixture::TYPE => EntryFieldTypeEnum::NOTE->value,
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_create_entry_field_file(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(
            attributes: [
                EntryFieldFixture::ENTRY => $entry,
                EntryFieldFixture::TYPE => EntryFieldTypeEnum::FILE,
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
            EntryFieldFixture::TYPE => EntryFieldTypeEnum::FILE->value,
        ]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_create_entry_field_totp(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(
            attributes: [
                EntryFieldFixture::ENTRY => $entry,
                EntryFieldFixture::TYPE => EntryFieldTypeEnum::TOTP,
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
            EntryFieldFixture::TYPE => EntryFieldTypeEnum::TOTP->value,
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
