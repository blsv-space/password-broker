<?php

declare(strict_types=1);

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

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = RefreshTokenRepository::getInstance();
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_save_a_refresh_token(): void
    {
        $token = $this->faker->sha256();

        $refreshToken = RefreshTokenFixture::create([RefreshTokenFixture::TOKEN => $token]);

        $this->repository->save($refreshToken);
        $this->assertDatabaseHas(RefreshTokenFixture::getTableName(), [RefreshTokenFixture::TOKEN => $token]);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_find_a_refresh_token_by_user(): void
    {
        $user = UserFixture::create(persist: true);

        $refreshToken = RefreshTokenFixture::create([RefreshTokenFixture::USER_ID => $user->id->toRaw()]);
        $this->repository->save($refreshToken);

        $foundRefreshToken = $this->repository->findByUserId($user->id);

        $this->assertNotNull($foundRefreshToken);
        $this->assertEquals($refreshToken->token, $foundRefreshToken->token);
    }

    /**
     * @throws PersistenceException
     */
    public function test_it_can_clean_expired_refresh_tokens(): void
    {
        RefreshTokenFixture::create(
            attributes: [RefreshTokenFixture::EXPIRATION_AT => $this->faker->dateTime('-1 hour')
                ->format(DateTime::FORMAT)],
            persist: true,
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
