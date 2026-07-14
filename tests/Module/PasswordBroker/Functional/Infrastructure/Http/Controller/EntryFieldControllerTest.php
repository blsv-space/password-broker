<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Functional\Infrastructure\Http\Controller;

use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\Identity\Infrastructure\Http\Controller\UserController;
use App\Module\PasswordBroker\Application\EntryField\DTO\EntryFieldResponse\DecryptedResponse;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldCreatedGeneralEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldDecryptedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldDeletedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldFileCreatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldFileUpdatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldLinkCreatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldNoteCreatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldPasswordCreatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldPasswordUpdatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldTotpCreatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldTotpUpdatedEvent;
use App\Module\PasswordBroker\Application\EntryField\Event\EntryFieldUpdatedGeneralEvent;
use App\Module\PasswordBroker\Domain\Entry\Entity\Entry;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldFile;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldPassword;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldTotp;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTotpHashAlgorithmEnum;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTitle;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Enum\RoleEnum;
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
use InvalidArgumentException;
use ReflectionException;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFieldFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupUserFixture;
use Tests\Shared\FunctionalTestCase;
use Tests\Shared\TestEventHandler;

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
        $this->assertEquals($entryField->id->toRaw(), $response[EntryFieldRepository::FIELD_ID]);
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

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryFieldCreatedGeneralEvent::class],
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
        $this->assertTrue($testEventHandler->wasDispatched(), "Event not dispatched");
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

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryFieldUpdatedGeneralEvent::class],
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
        $this->assertTrue($testEventHandler->wasDispatched(), "Event not dispatched");
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

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryFieldDeletedEvent::class],
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

        $this->assertTrue($testEventHandler->wasDispatched(), "Event not dispatched");
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
     * @throws PersistenceException
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     */
    public function test_is_should_return_encrypted_entry_field_value(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(attributes: [EntryFieldFixture::ENTRY => $entry], persist: true);

        $routeName = $this->buildRouteName($this->routePath, EntryFieldController::ACTION_ENCRYPTED);
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
        $this->assertEquals($entryField->id->toRaw(), $response[EntryFieldRepository::FIELD_ID]);
        $this->assertArrayHasKey(EntryFieldRepository::FIELD_VALUE_ENCRYPTED, $response);
        $this->assertEquals($entryField->valueEncrypted->toRaw(), $response[EntryFieldRepository::FIELD_VALUE_ENCRYPTED]);
        $this->assertArrayHasKey(EntryFieldRepository::FIELD_INITIALIZATION_VECTOR, $response);
        $this->assertEquals($entryField->initializationVector->toRaw(), $response[EntryFieldRepository::FIELD_INITIALIZATION_VECTOR]);
        $this->assertArrayHasKey(EntryFieldRepository::FIELD_TAG, $response);
        $this->assertEquals($entryField->tag->toRaw(), $response[EntryFieldRepository::FIELD_TAG]);
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

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryFieldDecryptedEvent::class],
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

        $this->assertTrue($testEventHandler->wasDispatched(), "Event not dispatched");
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
        );
        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_STORE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryFieldPasswordCreatedEvent::class],
        );

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

        $this->assertTrue($testEventHandler->wasDispatched(), "Event not dispatched");
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

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryFieldLinkCreatedEvent::class],
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

        $this->assertTrue($testEventHandler->wasDispatched(), "Event not dispatched");
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

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryFieldNoteCreatedEvent::class],
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

        $this->assertTrue($testEventHandler->wasDispatched(), "Event not dispatched");
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

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryFieldFileCreatedEvent::class],
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

        $this->assertTrue($testEventHandler->wasDispatched(), "Event not dispatched");
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

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryFieldTotpCreatedEvent::class],
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

        $this->assertTrue($testEventHandler->wasDispatched(), "Event not dispatched");
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_update_entry_field_password(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(
            attributes: [
                EntryFieldFixture::ENTRY => $entry,
                EntryFieldFixture::TYPE => EntryFieldTypeEnum::PASSWORD,
            ],
            persist: true,
        );
        $this->assertInstanceOf(EntryFieldPassword::class, $entryField);
        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_UPDATE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryFieldPasswordUpdatedEvent::class],
        );

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupRoute::PARAM_ENTRY_GROUP_ID => $entry->entryGroupId->toRaw(),
                EntryRoute::PARAM_ENTRY_ID => $entry->id->toRaw(),
                EntryFieldRoute::PARAM_ENTRY_FIELD_ID => $entryField->id->toRaw(),
            ],
        );

        $updatedLogin = $entryField->login->toRaw() . 'new';

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: [
                ...$entryField->getAsArray(),
                UserController::FIELD_MASTER_PASSWORD => UserFixture::DEFAULT_MASTER_PASSWORD,
                EntryFieldRepository::FIELD_LOGIN => $updatedLogin,
            ],
        );

        $this->assertEquals(HttpStatusCode::NO_CONTENT, $httpResponse->getStatusCode());

        $this->assertDatabaseHas(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ID => $entryField->id->toRaw(),
            EntryFieldFixture::TITLE => $entryField->title->toRaw(),
            EntryFieldFixture::TYPE => $entryField->type->toRaw(),

            EntryFieldFixture::LOGIN => $updatedLogin,
        ]);

        $this->assertTrue($testEventHandler->wasDispatched(), "Event not dispatched");
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_update_entry_field_file(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(
            attributes: [
                EntryFieldFixture::ENTRY => $entry,
                EntryFieldFixture::TYPE => EntryFieldTypeEnum::FILE,
            ],
            persist: true,
        );
        $this->assertInstanceOf(EntryFieldFile::class, $entryField);
        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_UPDATE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryFieldFileUpdatedEvent::class],
        );

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupRoute::PARAM_ENTRY_GROUP_ID => $entry->entryGroupId->toRaw(),
                EntryRoute::PARAM_ENTRY_ID => $entry->id->toRaw(),
                EntryFieldRoute::PARAM_ENTRY_FIELD_ID => $entryField->id->toRaw(),
            ],
        );

        $updatedFileName = $entryField->fileName->toRaw() . 'new';
        $updatedFileMime = $entryField->fileMime->toRaw() . 'new';
        $updatedFileSize = $entryField->fileSize->toRaw() + 1;

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: [
                ...$entryField->getAsArray(),
                UserController::FIELD_MASTER_PASSWORD => UserFixture::DEFAULT_MASTER_PASSWORD,
                EntryFieldRepository::FIELD_FILE_NAME => $updatedFileName,
                EntryFieldRepository::FIELD_FILE_MIME => $updatedFileMime,
                EntryFieldRepository::FIELD_FILE_SIZE => $updatedFileSize,
            ],
        );

        $this->assertEquals(HttpStatusCode::NO_CONTENT, $httpResponse->getStatusCode());

        $this->assertDatabaseHas(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ID => $entryField->id->toRaw(),
            EntryFieldFixture::TITLE => $entryField->title->toRaw(),
            EntryFieldFixture::TYPE => $entryField->type->toRaw(),

            EntryFieldFixture::FILE_NAME => $updatedFileName,
            EntryFieldFixture::FILE_MIME => $updatedFileMime,
            EntryFieldFixture::FILE_SIZE => $updatedFileSize,
        ]);

        $this->assertTrue($testEventHandler->wasDispatched(), "Event not dispatched");
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     */
    public function test_it_should_update_entry_field_totp(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(
            attributes: [
                EntryFieldFixture::ENTRY => $entry,
                EntryFieldFixture::TYPE => EntryFieldTypeEnum::TOTP,
            ],
            persist: true,
        );
        $this->assertInstanceOf(EntryFieldTotp::class, $entryField);
        $routeName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_UPDATE);
        $route = Router::getInstance()->getRouteByName($routeName);
        $this->assertNotNull($route, "Route $routeName not found");
        $httpMethod = $route->methods[0] ?? null;
        $this->assertNotNull($httpMethod, "Method not found for route $routeName");

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryFieldTotpUpdatedEvent::class],
        );

        $uri = $this->buildUri(
            path: $route->path,
            pathParams: [
                EntryGroupRoute::PARAM_ENTRY_GROUP_ID => $entry->entryGroupId->toRaw(),
                EntryRoute::PARAM_ENTRY_ID => $entry->id->toRaw(),
                EntryFieldRoute::PARAM_ENTRY_FIELD_ID => $entryField->id->toRaw(),
            ],
        );

        $updatedTotpHashAlgorithm = match ($entryField->totpHashAlgorithm->toRaw()) {
            EntryFieldTotpHashAlgorithmEnum::SHA256->value, EntryFieldTotpHashAlgorithmEnum::SHA1->value => EntryFieldTotpHashAlgorithmEnum::SHA512->value,
            EntryFieldTotpHashAlgorithmEnum::SHA512->value => EntryFieldTotpHashAlgorithmEnum::SHA256->value,
            default => throw new InvalidArgumentException('Unsupported TOTP hash algorithm'),
        };
        $updatedTotpTimeout = $entryField->totpTimeout->toRaw() + 1;

        $httpResponse = $this->sendRequest(
            method: $httpMethod,
            uri: $uri,
            body: [
                ...$entryField->getAsArray(),
                UserController::FIELD_MASTER_PASSWORD => UserFixture::DEFAULT_MASTER_PASSWORD,
                EntryFieldRepository::FIELD_TOTP_HASH_ALGORITHM => $updatedTotpHashAlgorithm,
                EntryFieldRepository::FIELD_TOTP_TIMEOUT => $updatedTotpTimeout,
            ],
        );

        $this->assertEquals(HttpStatusCode::NO_CONTENT, $httpResponse->getStatusCode());

        $this->assertDatabaseHas(EntryFieldFixture::getTableName(), [
            EntryFieldFixture::ID => $entryField->id->toRaw(),
            EntryFieldFixture::TITLE => $entryField->title->toRaw(),
            EntryFieldFixture::TYPE => $entryField->type->toRaw(),

            EntryFieldFixture::TOTP_HASH_ALGORITHM => $updatedTotpHashAlgorithm,
            EntryFieldFixture::TOTP_TIMEOUT => $updatedTotpTimeout,
        ]);

        $this->assertTrue($testEventHandler->wasDispatched(), "Event not dispatched");
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     * @throws EncryptionException
     */
    public function test_it_should_update_and_decrypt_value_of_entry_field(): void
    {
        $entry = $this->createAnEntry();
        $aesEncryptor = AesEncryptor::getInstance();
        $initialVectorProvider = InitialVectorProvider::getInstance();
        $value = $this->faker->text;
        $updatedValue = $value . '_updated';
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

        $routeUpdateName = $this->buildRouteName($this->routePath, RestControllerInterface::ACTION_UPDATE);
        $routeUpdate = Router::getInstance()->getRouteByName($routeUpdateName);
        $this->assertNotNull($routeUpdate, "Route $routeUpdateName not found");
        $httpUpdateMethod = $routeUpdate->methods[0] ?? null;
        $this->assertNotNull($httpUpdateMethod, "Method not found for route $routeUpdateName");

        $uriUpdate = $this->buildUri(
            path: $routeUpdate->path,
            pathParams: [
                EntryGroupRoute::PARAM_ENTRY_GROUP_ID => $entry->entryGroupId->toRaw(),
                EntryRoute::PARAM_ENTRY_ID => $entry->id->toRaw(),
                EntryFieldRoute::PARAM_ENTRY_FIELD_ID => $entryField->id->toRaw(),
            ],
        );

        $testEventUpdateHandler = new TestEventHandler(
            eventNames: [EntryFieldUpdatedGeneralEvent::class],
        );

        $httpUpdateResponse = $this->sendRequest(
            method: $httpUpdateMethod,
            uri: $uriUpdate,
            body: [
                ...$entryField->getAsArray(),
                UserController::FIELD_MASTER_PASSWORD => UserFixture::DEFAULT_MASTER_PASSWORD,
                EntryFieldController::FIELD_VALUE => $updatedValue,
            ],
        );

        $this->assertEquals(HttpStatusCode::NO_CONTENT, $httpUpdateResponse->getStatusCode());


        $routeDecryptName = $this->buildRouteName($this->routePath, EntryFieldController::ACTION_DECRYPT);
        $routeDecrypt = Router::getInstance()->getRouteByName($routeDecryptName);
        $this->assertNotNull($routeDecrypt, "Route $routeDecryptName not found");
        $httpDecryptMethod = $routeDecrypt->methods[0] ?? null;
        $this->assertNotNull($httpDecryptMethod, "Method not found for route $routeDecryptName");

        $uriDecrypt = $this->buildUri(
            path: $routeDecrypt->path,
            pathParams: [
                EntryGroupRoute::PARAM_ENTRY_GROUP_ID => $entry->entryGroupId->toRaw(),
                EntryRoute::PARAM_ENTRY_ID => $entry->id->toRaw(),
                EntryFieldRoute::PARAM_ENTRY_FIELD_ID => $entryField->id->toRaw(),
            ],
        );

        $testEventDecryptHandler = new TestEventHandler(
            eventNames: [EntryFieldDecryptedEvent::class],
        );

        $httpResponse = $this->sendRequest(
            method: $httpDecryptMethod,
            uri: $uriDecrypt,
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
        $this->assertEquals($updatedValue, $response[DecryptedResponse::FIELD_DECRYPTED_VALUE]);

        $this->assertTrue($testEventDecryptHandler->wasDispatched(), "Event Decrypt not dispatched");
        $this->assertTrue($testEventUpdateHandler->wasDispatched(), "Event Update not dispatched");
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
