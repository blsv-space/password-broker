<?php

namespace Tests\Module\Identity\Integration\Domain\RefreshToken\Service;

use App\Module\Identity\Domain\RefreshToken\Service\Exception\RefreshTokenException;
use App\Module\Identity\Domain\RefreshToken\Service\RefreshTokenService;
use App\Shared\Domain\ValueObject\DateTime;
use DateInterval;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\Identity\Fixture\RefreshTokenFixture;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Shared\IntegrationTestCase;

class RefreshTokenServiceTest extends IntegrationTestCase
{

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItShouldCreateARefreshToken(): void
    {
        $user = UserFixture::create(persist: true);
        $dateInterval = new DateInterval('PT1H');
        $refreshToken = RefreshTokenService::getInstance()->createRefreshToken(
            userId: $user->id,
            expiresIn: $dateInterval
        );
        $this->assertNotNull($refreshToken);
        $this->assertNotNull($refreshToken->token);
        $this->assertNotNull($refreshToken->expirationAt);
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
            RefreshTokenFixture::TOKEN => $refreshToken->token->toRaw(),
        ]);
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItShouldRemoveRefreshToken(): void
    {
        $user = UserFixture::create(persist: true);
        $dateInterval = new DateInterval('PT1H');
        $refreshToken = RefreshTokenService::getInstance()->createRefreshToken(
            userId: $user->id,
            expiresIn: $dateInterval,
        );
        $this->assertNotNull($refreshToken);
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
            RefreshTokenFixture::TOKEN => $refreshToken->token->toRaw(),
        ]);
        RefreshTokenService::getInstance()->removeRefreshToken($refreshToken->token);
        $this->assertDatabaseMissing(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
            RefreshTokenFixture::TOKEN => $refreshToken->token->toRaw(),
        ]);
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItShouldRemoveRefreshTokenByUser(): void
    {
        $user = UserFixture::create(persist: true);
        $dateInterval = new DateInterval('PT1H');
        $refreshToken = RefreshTokenService::getInstance()->createRefreshToken(
            userId: $user->id,
            expiresIn: $dateInterval,
        );
        $this->assertNotNull($refreshToken);
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
        ]);
        RefreshTokenService::getInstance()->removeRefreshTokenByUser($user);
        $this->assertDatabaseMissing(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
        ]);
    }

    /**
     * @return void
     * @throws PersistenceException
     * @throws RefreshTokenException
     */
    public function testItShouldFindRefreshTokenByToken(): void
    {
        $user = UserFixture::create(persist: true);
        $dateInterval = new DateInterval('PT1H');
        $refreshToken = RefreshTokenService::getInstance()->createRefreshToken(
            userId: $user->id,
            expiresIn: $dateInterval,
        );
        $this->assertNotNull($refreshToken);
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
        ]);
        $refreshTokenFound = RefreshTokenService::getInstance()->findByToken($refreshToken->token);
        $this->assertNotNull($refreshTokenFound);
    }

    /**
     * @return void
     * @throws PersistenceException
     * @throws RefreshTokenException
     */
    public function testItShouldRefreshTokenValidate(): void
    {
        $refreshTokenValid = RefreshTokenFixture::create([
            RefreshTokenFixture::EXPIRATION_AT => $this->faker->dateTimeBetween('+1 hour', '+1 year')
                ->format(DateTime::FORMAT)
        ]);

        $refreshTokenService = RefreshTokenService::getInstance();
        $refreshTokenService->refreshTokenValidate($refreshTokenValid);

        $this->assertTrue(true);
    }

    /**
     * @return void
     * @throws PersistenceException
     * @throws RefreshTokenException
     */
    public function testItShouldRefreshTokenValidateException(): void
    {
        $this->expectException(RefreshTokenException::class);
        $refreshTokenExpired = RefreshTokenFixture::create([
            RefreshTokenFixture::EXPIRATION_AT => $this->faker->dateTime('-1 hour')->format(DateTime::FORMAT)
        ]);
        RefreshTokenService::getInstance()->refreshTokenValidate($refreshTokenExpired);
    }
}