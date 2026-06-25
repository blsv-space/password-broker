<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Functional\Infrastructure\Http\Controller;

use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\Exception\RsaDomainServiceException;
use App\Module\Identity\Infrastructure\Http\Controller\UserController;
use App\Module\PasswordBroker\Application\EntryFieldHistory\DTO\EntryFieldHistoryResponse\DecryptedHistoryResponse;
use App\Module\PasswordBroker\Application\EntryFieldHistory\Event\EntryFieldHistoryDecryptedEvent;
use App\Module\PasswordBroker\Domain\Entry\Entity\Entry;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use App\Module\PasswordBroker\Domain\EntryGroupUser\Enum\RoleEnum;
use App\Module\PasswordBroker\Infrastructure\EntryFieldHistory\Repository\EntryFieldHistoryRepository;
use App\Module\PasswordBroker\Infrastructure\Http\Controller\EntryFieldHistoryController;
use App\Module\PasswordBroker\Infrastructure\Http\Route\EntryFieldHistoryRoute;
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
use Tests\Module\PasswordBroker\Fixture\EntryFieldHistoryFixture;
use Tests\Module\PasswordBroker\Fixture\EntryFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupFixture;
use Tests\Module\PasswordBroker\Fixture\EntryGroupUserFixture;
use Tests\Shared\FunctionalTestCase;
use Tests\Shared\TestEventHandler;

class EntryFieldHistoryControllerTest extends FunctionalTestCase
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
            EntryFieldHistoryRoute::GROUP_NAME,
        ];

        $this->userActor = UserFixture::create(persist: true);
        $this->actAs($this->userActor);
    }

    /**
     * @throws PersistenceException
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_return_entry_field_histories(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(attributes: [EntryFieldFixture::ENTRY => $entry], persist: true);
        $entryFieldHistory = EntryFieldHistoryFixture::create(attributes: [EntryFieldHistoryFixture::ENTRY_FIELD => $entryField], persist: true);

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
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('pagination', $response);
        $this->assertCount(1, $response['data']);
        $this->assertEquals($entryFieldHistory->id->toRaw(), $response['data'][0][EntryFieldHistoryRepository::FIELD_ID]);
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
        $entryField = EntryFieldFixture::create(attributes: [EntryFieldFixture::ENTRY => $entry], persist: true);
        EntryFieldHistoryFixture::create(attributes: [EntryFieldHistoryFixture::ENTRY_FIELD => $entryField], persist: true);

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
     * @throws PersistenceException
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     */
    public function test_it_should_show_entry_field_history(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(attributes: [EntryFieldFixture::ENTRY => $entry], persist: true);
        $entryFieldHistory = EntryFieldHistoryFixture::create(attributes: [EntryFieldHistoryFixture::ENTRY_FIELD => $entryField], persist: true);

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
                EntryFieldHistoryRoute::PARAM_ENTRY_FIELD_HISTORY_ID => $entryFieldHistory->id->toRaw(),
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
        $this->assertArrayHasKey(EntryFieldHistoryRepository::FIELD_ID, $response);
        $this->assertEquals($entryFieldHistory->id->toRaw(), $response[EntryFieldHistoryRepository::FIELD_ID]);
        $this->assertArrayHasKey(EntryFieldHistoryRepository::FIELD_ENTRY_FIELD_ID, $response);
        $this->assertEquals($entryField->id->toRaw(), $response[EntryFieldHistoryRepository::FIELD_ENTRY_FIELD_ID]);
        $this->assertArrayHasKey(EntryFieldHistoryRepository::FIELD_TITLE, $response);
        $this->assertEquals($entryFieldHistory->title->toRaw(), $response[EntryFieldHistoryRepository::FIELD_TITLE]);
        $this->assertArrayHasKey(EntryFieldHistoryRepository::FIELD_EVENT_NAME, $response);
        $this->assertEquals($entryFieldHistory->eventName->toRaw(), $response[EntryFieldHistoryRepository::FIELD_EVENT_NAME]);
        $this->assertArrayHasKey(EntryFieldHistoryRepository::FIELD_CREATED_AT, $response);
        $this->assertArrayHasKey(EntryFieldHistoryRepository::FIELD_CREATED_BY, $response);
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
        $entryFieldHistory = EntryFieldHistoryFixture::create(attributes: [EntryFieldHistoryFixture::ENTRY_FIELD => $entryField], persist: true);

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
                EntryFieldHistoryRoute::PARAM_ENTRY_FIELD_HISTORY_ID => $entryFieldHistory->id->toRaw(),
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
    public function test_is_should_return_encrypted_entry_field_history_value(): void
    {
        $entry = $this->createAnEntry();
        $entryField = EntryFieldFixture::create(attributes: [EntryFieldFixture::ENTRY => $entry], persist: true);
        $entryFieldHistory = EntryFieldHistoryFixture::create(attributes: [EntryFieldHistoryFixture::ENTRY_FIELD => $entryField], persist: true);

        $routeName = $this->buildRouteName($this->routePath, EntryFieldHistoryController::ACTION_ENCRYPTED);
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
                EntryFieldHistoryRoute::PARAM_ENTRY_FIELD_HISTORY_ID => $entryFieldHistory->id->toRaw(),
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
        $this->assertArrayHasKey(EntryFieldHistoryRepository::FIELD_ID, $response);
        $this->assertEquals($entryFieldHistory->id->toRaw(), $response[EntryFieldHistoryRepository::FIELD_ID]);
        $this->assertArrayHasKey(EntryFieldHistoryRepository::FIELD_VALUE_ENCRYPTED, $response);
        $this->assertEquals($entryFieldHistory->valueEncrypted->toRaw(), $response[EntryFieldHistoryRepository::FIELD_VALUE_ENCRYPTED]);
        $this->assertArrayHasKey(EntryFieldHistoryRepository::FIELD_INITIALIZATION_VECTOR, $response);
        $this->assertEquals($entryFieldHistory->initializationVector->toRaw(), $response[EntryFieldHistoryRepository::FIELD_INITIALIZATION_VECTOR]);
        $this->assertArrayHasKey(EntryFieldHistoryRepository::FIELD_TAG, $response);
        $this->assertEquals($entryFieldHistory->tag->toRaw(), $response[EntryFieldHistoryRepository::FIELD_TAG]);
    }

    /**
     * @throws RouteNotFoundException
     * @throws RsaDomainServiceException
     * @throws PersistenceException
     * @throws EncryptionException
     */
    public function test_it_should_decrypt_entry_field_history(): void
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
        $entryFieldHistory = EntryFieldHistoryFixture::create(
            attributes: [
                EntryFieldHistoryFixture::ENTRY_FIELD => $entryField,
                EntryFieldHistoryFixture::VALUE_ENCRYPTED => $aesEncryptedData->encryptedData,
                EntryFieldHistoryFixture::INITIALIZATION_VECTOR => $iv,
                EntryFieldHistoryFixture::TAG => $aesEncryptedData->tag,
                EntryFieldHistoryFixture::TYPE => EntryFieldTypeEnum::PASSWORD,
            ],
            persist: true,
        );


        $routeName = $this->buildRouteName($this->routePath, EntryFieldHistoryController::ACTION_DECRYPT);
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
                EntryFieldHistoryRoute::PARAM_ENTRY_FIELD_HISTORY_ID => $entryFieldHistory->id->toRaw(),
            ],
        );

        $testEventHandler = new TestEventHandler(
            eventNames: [EntryFieldHistoryDecryptedEvent::class],
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
        $this->assertArrayHasKey(DecryptedHistoryResponse::FIELD_ENTRY_FIELD_HISTORY_ID, $response);
        $this->assertEquals($entryFieldHistory->id->toRaw(), $response[DecryptedHistoryResponse::FIELD_ENTRY_FIELD_HISTORY_ID]);
        $this->assertArrayHasKey(DecryptedHistoryResponse::FIELD_DECRYPTED_VALUE, $response);
        $this->assertEquals($value, $response[DecryptedHistoryResponse::FIELD_DECRYPTED_VALUE]);

        $this->assertTrue($testEventHandler->wasDispatched(), "Event not dispatched");
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
