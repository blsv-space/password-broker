<?php

namespace Tests\Module\Identity\Integration\Infrastructure\User\Repository;

use App\Module\Identity\Infrastructure\User\Repository\RefreshTokenRepository;
use App\Shared\Domain\ValueObject\DateTime;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\Identity\Fixture\RefreshTokenFixture;
use Tests\Module\Identity\Fixture\UserFixture;
use Tests\Shared\IntegrationTestCase;

class RefreshTokenRepositoryTest extends IntegrationTestCase
{
    private RefreshTokenRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = RefreshTokenRepository::getInstance();
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItCanSaveARefreshToken(): void
    {
        $token = $this->faker->sha256();

        $refreshToken = RefreshTokenFixture::create([RefreshTokenFixture::TOKEN => $token]);

        $this->repository->save($refreshToken);
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [RefreshTokenFixture::TOKEN => $token]);
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItCanFindARefreshTokenByUser(): void
    {
        $user = UserFixture::create(persist: true);

        $refreshToken = RefreshTokenFixture::create([RefreshTokenFixture::USER_ID => $user->id->toRaw()]);
        $this->repository->save($refreshToken);

        $foundRefreshToken = $this->repository->findByUserId($user->id);

        $this->assertNotNull($foundRefreshToken);
        $this->assertEquals($refreshToken->token, $foundRefreshToken->token);
    }

    /**
     * @return void
     * @throws PersistenceException
     */
    public function testItCanCleanExpiredRefreshTokens(): void
    {
        RefreshTokenFixture::create(
            attributes: [RefreshTokenFixture::EXPIRATION_AT => $this->faker->dateTime('-1 hour')
                ->format(DateTime::FORMAT)],
            persist: true
        );
        RefreshTokenFixture::create(persist: true);

        $this->assertCount(
            expectedCount: 2,
            haystack: $this->repository->findAll(),
        );

        $this->repository->cleanExpiredTokens();


        $this->assertCount(
            expectedCount: 1,
            haystack: $this->repository->findAll(),
        );
    }
}