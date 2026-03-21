<?php

declare(strict_types=1);

namespace Identity\Integration\Application\User\Service;

use App\Module\Identity\Application\User\Service\Exception\RefreshTokenException;
use App\Module\Identity\Application\User\Service\RefreshTokenApplicationService;
use DateInterval;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\Identity\Fixture\RefreshTokenFixture;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Shared\IntegrationTestCase;

class RefreshTokenApplicationServiceTest extends IntegrationTestCase
{
    /**
     * @throws PersistenceException
     */
    public function test_it_should_create_a_refresh_token(): void
    {
        $user = UserFixture::create(persist: true);
        $dateInterval = new DateInterval('PT1H');
        $refreshToken = RefreshTokenApplicationService::getInstance()->createRefreshToken(
            userId: $user->id,
            expiresIn: $dateInterval,
        );
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
            RefreshTokenFixture::TOKEN => $refreshToken->token->toRaw(),
        ]);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_remove_refresh_token(): void
    {
        $user = UserFixture::create(persist: true);
        $dateInterval = new DateInterval('PT1H');
        $refreshToken = RefreshTokenApplicationService::getInstance()->createRefreshToken(
            userId: $user->id,
            expiresIn: $dateInterval,
        );
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
            RefreshTokenFixture::TOKEN => $refreshToken->token->toRaw(),
        ]);
        RefreshTokenApplicationService::getInstance()->removeRefreshToken($refreshToken->token);
        $this->assertDatabaseMissing(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
            RefreshTokenFixture::TOKEN => $refreshToken->token->toRaw(),
        ]);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_should_remove_refresh_token_by_user(): void
    {
        $user = UserFixture::create(persist: true);
        $dateInterval = new DateInterval('PT1H');
        RefreshTokenApplicationService::getInstance()->createRefreshToken(
            userId: $user->id,
            expiresIn: $dateInterval,
        );
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
        ]);
        RefreshTokenApplicationService::getInstance()->removeRefreshTokenByUser($user);
        $this->assertDatabaseMissing(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
        ]);
    }

    /**
     * @throws PersistenceException
     * @throws RefreshTokenException
     */
    public function test_it_should_find_refresh_token_by_token(): void
    {
        $user = UserFixture::create(persist: true);
        $dateInterval = new DateInterval('PT1H');
        $refreshToken = RefreshTokenApplicationService::getInstance()->createRefreshToken(
            userId: $user->id,
            expiresIn: $dateInterval,
        );
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [
            RefreshTokenFixture::USER_ID => $user->id->toRaw(),
        ]);
        $refreshTokenFound = RefreshTokenApplicationService::getInstance()->findByToken($refreshToken->token);
        $this->assertNotNull($refreshTokenFound);
    }
}
