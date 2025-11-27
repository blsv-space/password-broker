<?php

namespace Tests\Module\Identity\Integration\Domain\User\Service;

use App\Module\Identity\Domain\RefreshToken\Service\Exception\RefreshTokenException;
use App\Module\Identity\Domain\RefreshToken\ValueObject\Token;
use App\Module\Identity\Domain\User\Service\AuthDomainService;
use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;
use App\Shared\Infrastructure\Security\Exception\JwtTokenExpiredException;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use JsonException;
use Tests\Module\Identity\Fixture\RefreshTokenFixture;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Shared\IntegrationTestCase;

class AuthDomainServiceTest extends IntegrationTestCase
{
    /**
     * @return void
     */
    public function testItShouldCreateAHashedPassword(): void
    {
        $password = $this->faker->password();

        $authDomainService = AuthDomainService::getInstance();
        $passwordHashed = $authDomainService->hashPassword($password);
        $this->assertNotEmpty($passwordHashed);
        $this->assertNotEquals($password, $passwordHashed);
        $this->assertTrue($authDomainService->verifyPasswordHash(
            passwordHash: $passwordHashed,
            password: $password,
        ));
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItShouldValidatePasswordByUser(): void
    {
        $password = $this->faker->password();
        $authDomainService = AuthDomainService::getInstance();
        $user = UserFixture::create([UserFixture::HASHED_PASSWORD => $authDomainService->hashPassword($password)]);
        $this->assertTrue($authDomainService->verifyPasswordByUser(
            user: $user,
            password: $password,
        ));
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItShouldValidatePasswordByUserWithInvalidPassword(): void
    {
        $password = $this->faker->password();
        $authDomainService = AuthDomainService::getInstance();
        $user = UserFixture::create([UserFixture::HASHED_PASSWORD => $authDomainService->hashPassword($password)]);
        $this->assertFalse($authDomainService->verifyPasswordByUser(
            user: $user,
            password: $this->faker->password(),
        ));
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItShouldLoginUser(): void
    {
        $user = UserFixture::create(persist: true);
        $loginResponseDTO = AuthDomainService::getInstance()->login($user);
        $this->assertNotEmpty($loginResponseDTO->refreshToken);
        $this->assertNotEmpty($loginResponseDTO->jwtToken);
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::TOKEN => $loginResponseDTO->refreshToken,
        ]);
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItShouldLogoutUser(): void
    {
        $user = UserFixture::create(persist: true);
        $loginResponseDTO = AuthDomainService::getInstance()->login($user);
        $this->assertNotEmpty($loginResponseDTO->refreshToken);
        $this->assertNotEmpty($loginResponseDTO->jwtToken);
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
            RefreshTokenFixture::TOKEN => $loginResponseDTO->refreshToken,
        ]);
        AuthDomainService::getInstance()->logout($user);
        $this->assertDatabaseMissing(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
        ]);
    }

    /**
     * @return void
     * @throws PersistenceException
     * @throws RefreshTokenException
     */
    public function testItShouldRefreshToken(): void
    {
        $user = UserFixture::create(persist: true);
        $loginResponseDTO = AuthDomainService::getInstance()->login($user);
        $this->assertNotEmpty($loginResponseDTO->refreshToken);
        $this->assertNotEmpty($loginResponseDTO->jwtToken);
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
            RefreshTokenFixture::TOKEN => $loginResponseDTO->refreshToken,
        ]);

        $loginResponseDTOByRefreshToken = AuthDomainService::getInstance()
            ->refreshToken(Token::fromRaw($loginResponseDTO->refreshToken));
        $this->assertNotEmpty($loginResponseDTOByRefreshToken->refreshToken);
        $this->assertNotEmpty($loginResponseDTOByRefreshToken->jwtToken);
        $this->assertNotEquals($loginResponseDTO->refreshToken, $loginResponseDTOByRefreshToken->refreshToken);
        $this->assertDatabaseMissing(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
            RefreshTokenFixture::TOKEN => $loginResponseDTO->refreshToken,
        ]);
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
            RefreshTokenFixture::TOKEN => $loginResponseDTOByRefreshToken->refreshToken,
        ]);
    }

    /**
     * @return void
     * @throws PersistenceException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws JsonException
     */
    public function testItShouldAuthUserByJWTToken(): void
    {
        $user = UserFixture::create(persist: true);
        $loginResponseDTO = AuthDomainService::getInstance()->login($user);
        $this->assertNotEmpty($loginResponseDTO->refreshToken);
        $this->assertNotEmpty($loginResponseDTO->jwtToken);
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
            RefreshTokenFixture::TOKEN => $loginResponseDTO->refreshToken,
        ]);
        $authenticatedUser = AuthDomainService::getInstance()->authByJwtToken($loginResponseDTO->jwtToken);

        $this->assertTrue($user->id->equals($authenticatedUser->id));
    }

    /**
     * @return void
     */
    public function testItShouldGetJwtSecret(): void
    {
        $jwtSecret = AuthDomainService::getInstance()->getJwtSecret();
        $this->assertNotEmpty($jwtSecret);
    }

    /**
     * @return void
     */
    public function testItShouldGetJwtAlgo(): void
    {
        $jwtAlgoEnum = AuthDomainService::getInstance()->getJwtAlgorithm();
        $this->assertNotEmpty($jwtAlgoEnum);
    }

    /**
     * @return void
     */
    public function testItShouldGetJwtTtl(): void
    {
        $jwtTtl = AuthDomainService::getInstance()->getJwtTtl();
        $this->assertNotEmpty($jwtTtl);
    }

}