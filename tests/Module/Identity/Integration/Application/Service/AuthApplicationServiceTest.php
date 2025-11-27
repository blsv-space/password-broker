<?php

namespace Tests\Module\Identity\Integration\Application\Service;

use App\Module\Identity\Application\User\Service\AuthApplicationService;
use App\Module\Identity\Application\User\Service\Exception\AuthInvalidPasswordException;
use App\Module\Identity\Application\User\Service\Exception\AuthUserNotFoundException;
use App\Module\Identity\Domain\RefreshToken\Service\Exception\RefreshTokenException;
use App\Module\Identity\Domain\RefreshToken\ValueObject\Token;
use App\Module\Identity\Infrastructure\Security\PasswordHasher;
use App\Shared\Domain\ValueObject\DateTime;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\Identity\Fixture\RefreshTokenFixture;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Shared\IntegrationTestCase;

class AuthApplicationServiceTest extends IntegrationTestCase
{
    /**
     * @return void
     * @throws AuthInvalidPasswordException
     * @throws AuthUserNotFoundException
     * @throws PersistenceException
     */
    public function testItShouldLoginUser(): void
    {
        $userName = $this->faker->userName();
        $password = $this->faker->password();
        $passwordHash = PasswordHasher::getInstance()->hash($password);
        UserFixture::create(
            attributes: [
                UserFixture::USER_NAME => $userName,
                UserFixture::HASHED_PASSWORD => $passwordHash,
            ],
            persist: true,
        );
        $loginResponseDTO = AuthApplicationService::getInstance()->login($userName, $password);
        $this->assertNotEmpty($loginResponseDTO->jwtToken);
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::TOKEN => $loginResponseDTO->refreshToken,
        ]);
    }

    /**
     * @return void
     * @throws AuthInvalidPasswordException
     * @throws AuthUserNotFoundException
     * @throws PersistenceException
     */
    public function testItShouldThrowExceptionIfUserNotFound(): void
    {
        $userName = $this->faker->userName();
        $password = $this->faker->password();
        $this->expectException(AuthUserNotFoundException::class);
        AuthApplicationService::getInstance()->login($userName, $password);
    }

    /**
     * @return void
     * @throws AuthInvalidPasswordException
     * @throws AuthUserNotFoundException
     * @throws PersistenceException
     */
    public function testItShouldThrowExceptionIfPasswordIsInvalid(): void
    {
        $userName = $this->faker->userName();
        $password = $this->faker->password();
        UserFixture::create(
            attributes: [
                UserFixture::USER_NAME => $userName,
                UserFixture::HASHED_PASSWORD => PasswordHasher::getInstance()->hash($this->faker->password()),
            ],
            persist: true,
        );
        $this->expectException(AuthInvalidPasswordException::class);
        AuthApplicationService::getInstance()->login($userName, $password);
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItShouldLogoutUser(): void
    {
        $userName = $this->faker->userName();
        $password = $this->faker->password();
        $user = UserFixture::create(
            attributes: [
                UserFixture::USER_NAME => $userName,
                UserFixture::HASHED_PASSWORD => PasswordHasher::getInstance()->hash($password),
            ],
            persist: true,
        );
        RefreshTokenFixture::create(
            attributes: [
                RefreshTokenFixture::USER_ID => $user->id->toRaw(),
            ],
            persist: true,
        );

        $this->assertDatabaseHas(
            table: RefreshTokenFixture::getTableName(),
            param: [RefreshTokenFixture::USER_ID => $user->id->toRaw()],
        );

        AuthApplicationService::getInstance()->logout($user);

        $this->assertDatabaseMissing(
            table: RefreshTokenFixture::getTableName(),
            param: [RefreshTokenFixture::USER_ID => $user->id->toRaw()],
        );
    }

    /**
     * @return void
     * @throws PersistenceException
     * @throws RefreshTokenException
     */
    public function testItShouldRefreshUserToken(): void
    {
        $userName = $this->faker->userName();
        $password = $this->faker->password();
        $user = UserFixture::create(
            attributes: [
                UserFixture::USER_NAME => $userName,
                UserFixture::HASHED_PASSWORD => PasswordHasher::getInstance()->hash($password),
            ],
            persist: true,
        );
        $refreshToken = RefreshTokenFixture::create(
            attributes: [
                RefreshTokenFixture::USER_ID => $user->id->toRaw(),
            ],
            persist: true,
        );

        $this->assertDatabaseHas(
            table: RefreshTokenFixture::getTableName(),
            param: [RefreshTokenFixture::USER_ID => $user->id->toRaw()],
        );

        $refreshTokenNew = AuthApplicationService::getInstance()->refreshToken($refreshToken->token)->refreshToken;

        $this->assertNotEquals($refreshToken->token, $refreshTokenNew);
        $this->assertDatabaseMissing(
            table: RefreshTokenFixture::getTableName(),
            param: [RefreshTokenFixture::TOKEN => $refreshToken->token->toRaw()],
        );
        $this->assertDatabaseHas(
            table: RefreshTokenFixture::getTableName(),
            param: [RefreshTokenFixture::TOKEN => $refreshTokenNew],
        );
    }

    /**
     * @return void
     * @throws PersistenceException
     * @throws RefreshTokenException
     */
    public function testItShouldThrowExceptionIfRefreshTokenIsInvalid(): void
    {
        $this->expectException(RefreshTokenException::class);
        AuthApplicationService::getInstance()->refreshToken(
            token: Token::fromRaw($this->faker->sha256())
        );
    }

    /**
     * @return void
     * @throws PersistenceException
     * @throws RefreshTokenException
     */
    public function testItShouldThrowExceptionIfRefreshTokenIsExpired(): void
    {
        $userName = $this->faker->userName();
        $password = $this->faker->password();
        $user = UserFixture::create(
            attributes: [
                UserFixture::USER_NAME => $userName,
                UserFixture::HASHED_PASSWORD => PasswordHasher::getInstance()->hash($password),
            ],
            persist: true,
        );

        $refreshToken = RefreshTokenFixture::create(
            attributes: [
                RefreshTokenFixture::USER_ID => $user->id->toRaw(),
                RefreshTokenFixture::EXPIRATION_AT => $this->faker->dateTime('-1 hour')->format(DateTime::FORMAT)
            ],
            persist: true,
        );

        $this->assertDatabaseHas(
            table: RefreshTokenFixture::getTableName(),
            param: [RefreshTokenFixture::USER_ID => $user->id->toRaw()],
        );
        $this->expectException(RefreshTokenException::class);
        AuthApplicationService::getInstance()->refreshToken($refreshToken->token)->refreshToken;
    }

    public function testItShouldAuthUserByJWTToken(): void
    {
        $userName = $this->faker->userName();
        $password = $this->faker->password();
        $passwordHash = PasswordHasher::getInstance()->hash($password);
        UserFixture::create(
            attributes: [
                UserFixture::USER_NAME => $userName,
                UserFixture::HASHED_PASSWORD => $passwordHash,
            ],
            persist: true,
        );
        $authApplicationService = AuthApplicationService::getInstance();
        $loginResponseDTO = $authApplicationService->login($userName, $password);
        $this->assertNotEmpty($loginResponseDTO->jwtToken);
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::TOKEN => $loginResponseDTO->refreshToken,
        ]);
        $userNull = $authApplicationService->authUser(disableCache: true);
        $this->assertNull($userNull);
        $user = $authApplicationService->authUser(
            token: $loginResponseDTO->jwtToken,
            disableCache: true,
        );
        $this->assertNotNull($user);
        $this->assertEquals($userName, $user->userName->toRaw());
    }

}