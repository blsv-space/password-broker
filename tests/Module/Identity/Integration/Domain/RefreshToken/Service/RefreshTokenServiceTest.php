<?php

declare(strict_types=1);

namespace Tests\Module\Identity\Integration\Domain\RefreshToken\Service;

use App\Module\Identity\Domain\RefreshToken\Service\Exception\RefreshTokenDomainException;
use App\Module\Identity\Domain\RefreshToken\Service\RefreshTokenDomainService;
use App\Shared\Domain\ValueObject\DateTime;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Module\Identity\Fixture\RefreshTokenFixture;
use Tests\Shared\IntegrationTestCase;

class RefreshTokenServiceTest extends IntegrationTestCase
{
    /**
     * @throws PersistenceException
     * @throws RefreshTokenDomainException
     */
    public function test_it_should_refresh_token_validate(): void
    {
        $refreshTokenValid = RefreshTokenFixture::create([
            RefreshTokenFixture::EXPIRATION_AT => $this->faker->dateTimeBetween('+1 hour', '+1 year')
                ->format(DateTime::FORMAT),
        ]);

        $refreshTokenService = RefreshTokenDomainService::getInstance();
        $refreshTokenService->refreshTokenValidate($refreshTokenValid);

        $this->assertTrue(true);
    }

    /**
     * @throws PersistenceException
     * @throws RefreshTokenDomainException
     */
    public function test_it_should_refresh_token_validate_exception(): void
    {
        $this->expectException(RefreshTokenDomainException::class);
        $refreshTokenExpired = RefreshTokenFixture::create([
            RefreshTokenFixture::EXPIRATION_AT => $this->faker->dateTime('-1 hour')->format(DateTime::FORMAT),
        ]);
        RefreshTokenDomainService::getInstance()->refreshTokenValidate($refreshTokenExpired);
    }
}
