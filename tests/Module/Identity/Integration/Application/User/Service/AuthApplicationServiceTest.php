<?php

declare(strict_types=1);

namespace Tests\Module\Identity\Integration\Application\User\Service;

use App\Module\Identity\Application\User\Service\AuthApplicationService;
use App\Module\Identity\Application\User\Service\Exception\AuthInvalidPasswordException;
use App\Module\Identity\Application\User\Service\Exception\AuthUserNotFoundException;
use App\Module\Identity\Application\User\Service\Exception\RefreshTokenException;
use App\Module\Identity\Domain\RefreshToken\Service\Exception\RefreshTokenDomainException;
use App\Module\Identity\Domain\RefreshToken\ValueObject\Token;
use App\Module\Identity\Infrastructure\Security\PasswordHasher;
use App\Shared\Domain\ValueObject\DateTime;
use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;
use App\Shared\Infrastructure\Security\Exception\JwtTokenExpiredException;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\Identity\Fixture\RefreshTokenFixture;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Shared\IntegrationTestCase;

class AuthApplicationServiceTest extends IntegrationTestCase
{
    /**
     * @throws AuthInvalidPasswordException
     * @throws AuthUserNotFoundException
     * @throws PersistenceException
     */
    public function test_it_should_login_user(): void
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
        $loginResponseDto = AuthApplicationService::getInstance()->login($userName, $password);
        $this->assertNotEmpty($loginResponseDto->jwtToken);
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::TOKEN => $loginResponseDto->refreshToken,
        ]);
    }

    /**
     * @throws AuthInvalidPasswordException
     * @throws AuthUserNotFoundException
     * @throws PersistenceException
     */
    public function test_it_should_throw_exception_if_user_not_found(): void
    {
        $userName = $this->faker->userName();
        $password = $this->faker->password();
        $this->expectException(AuthUserNotFoundException::class);
        AuthApplicationService::getInstance()->login($userName, $password);
    }

    /**
     * @throws AuthInvalidPasswordException
     * @throws AuthUserNotFoundException
     * @throws PersistenceException
     */
    public function test_it_should_throw_exception_if_password_is_invalid(): void
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
     * @throws PersistenceException
     */
    public function test_it_should_logout_user(): void
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
     * @throws PersistenceException
     * @throws RefreshTokenDomainException
     */
    public function test_it_should_refresh_user_token(): void
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
     * @throws PersistenceException
     * @throws RefreshTokenDomainException
     * @throws RefreshTokenException
     */
    public function test_it_should_throw_exception_if_refresh_token_is_invalid(): void
    {
        $this->expectException(RefreshTokenException::class);
        AuthApplicationService::getInstance()->refreshToken(
            token: Token::fromRaw($this->faker->sha256()),
        );
    }

    /**
     * @throws PersistenceException
     * @throws RefreshTokenDomainException
     * @throws RefreshTokenException
     */
    public function test_it_should_throw_exception_if_refresh_token_is_expired(): void
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
                RefreshTokenFixture::EXPIRATION_AT => $this->faker->dateTime('-1 hour')->format(DateTime::FORMAT),
            ],
            persist: true,
        );

        $this->assertDatabaseHas(
            table: RefreshTokenFixture::getTableName(),
            param: [RefreshTokenFixture::USER_ID => $user->id->toRaw()],
        );
        $this->expectException(RefreshTokenDomainException::class);
        AuthApplicationService::getInstance()->refreshToken($refreshToken->token)->refreshToken;
    }

    /**
     * @throws JwtTokenExpiredException
     * @throws JwtInvalidTokenException
     * @throws AuthInvalidPasswordException
     * @throws AuthUserNotFoundException
     * @throws PersistenceException
     */
    public function test_it_should_auth_user_by_jwt_token(): void
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
        $loginResponseDto = $authApplicationService->login($userName, $password);
        $this->assertNotEmpty($loginResponseDto->jwtToken);
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::TOKEN => $loginResponseDto->refreshToken,
        ]);
        $userNull = $authApplicationService->authUser(disableCache: true);
        $this->assertNull($userNull);
        $user = $authApplicationService->authUser(
            token: $loginResponseDto->jwtToken,
            disableCache: true,
        );
        $this->assertNotNull($user);
        $this->assertEquals($userName, $user->userName->toRaw());
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_auth_user(): void
    {
        $user = UserFixture::create(persist: true);
        $loginResponseDto = AuthApplicationService::getInstance()->auth($user);
        $this->assertNotEmpty($loginResponseDto->refreshToken);
        $this->assertNotEmpty($loginResponseDto->jwtToken);
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::TOKEN => $loginResponseDto->refreshToken,
        ]);
    }

    /**
     * @throws PersistenceException
     * @throws RefreshTokenDomainException
     * @throws RefreshTokenException
     */
    public function test_it_should_refresh_token(): void
    {
        $user = UserFixture::create(persist: true);
        $loginResponseDto = AuthApplicationService::getInstance()->auth($user);
        $this->assertNotEmpty($loginResponseDto->refreshToken);
        $this->assertNotEmpty($loginResponseDto->jwtToken);
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
            RefreshTokenFixture::TOKEN => $loginResponseDto->refreshToken,
        ]);

        $loginResponseDtoByRefreshToken = AuthApplicationService::getInstance()
            ->refreshToken(Token::fromRaw($loginResponseDto->refreshToken));
        $this->assertNotEmpty($loginResponseDtoByRefreshToken->refreshToken);
        $this->assertNotEmpty($loginResponseDtoByRefreshToken->jwtToken);
        $this->assertNotEquals($loginResponseDto->refreshToken, $loginResponseDtoByRefreshToken->refreshToken);
        $this->assertDatabaseMissing(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
            RefreshTokenFixture::TOKEN => $loginResponseDto->refreshToken,
        ]);
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
            RefreshTokenFixture::TOKEN => $loginResponseDtoByRefreshToken->refreshToken,
        ]);
    }
}
