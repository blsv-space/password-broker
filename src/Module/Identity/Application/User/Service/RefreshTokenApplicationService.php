<?php

declare(strict_types=1);

namespace App\Module\Identity\Application\User\Service;

use App\Module\Identity\Application\User\Service\Exception\RefreshTokenException;
use App\Module\Identity\Domain\RefreshToken\Entity\RefreshToken;
use App\Module\Identity\Domain\RefreshToken\Repository\RefreshTokenRepositoryInterface;
use App\Module\Identity\Domain\RefreshToken\ValueObject\ExpirationAt;
use App\Module\Identity\Domain\RefreshToken\ValueObject\RefreshTokenId;
use App\Module\Identity\Domain\RefreshToken\ValueObject\Token;
use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\Identity\Infrastructure\User\Repository\RefreshTokenRepository;
use App\Shared\Domain\Service\OpaqueTokenGeneratorInterface;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Infrastructure\Security\OpaqueTokenGenerator;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Inquisition\Core\Application\Service\ApplicationServiceInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Core\Infrastructure\Persistence\Repository\QueryCriteria;
use Inquisition\Foundation\Singleton\SingletonTrait;

class RefreshTokenApplicationService implements ApplicationServiceInterface
{
    use SingletonTrait;

    private RefreshTokenRepositoryInterface $refreshTokenRepository;
    private OpaqueTokenGeneratorInterface $opaqueTokenGenerator;

    private function __construct()
    {
        $this->refreshTokenRepository = RefreshTokenRepository::getInstance();
        $this->opaqueTokenGenerator = new OpaqueTokenGenerator();
    }

    /**
     * @throws PersistenceException
     */
    public function createRefreshToken(
        UserId       $userId,
        DateInterval $expiresIn,
    ): RefreshToken {
        $token = $this->opaqueTokenGenerator->generate();
        $expiresAt = DateTimeImmutable::createFromMutable(new DateTime()->add($expiresIn));
        $refreshToken = new RefreshToken(
            id: RefreshTokenId::generate(),
            userId: $userId,
            token: Token::fromRaw($token),
            expirationAt: ExpirationAt::fromDateTime($expiresAt),
            createdAt: CreatedAt::now(),
        );

        $this->refreshTokenRepository->insert($refreshToken);

        return $refreshToken;
    }

    /**
     * @throws PersistenceException
     */
    public function removeRefreshToken(Token $token): void
    {
        $this->refreshTokenRepository->removeBy([new QueryCriteria(RefreshTokenRepository::FIELD_TOKEN, $token)]);
    }

    /**
     * @throws PersistenceException
     */
    public function removeRefreshTokenByUser(User $user): void
    {
        $this->refreshTokenRepository->removeBy([new QueryCriteria(RefreshTokenRepository::FIELD_USER_ID, $user->id->toRaw())]);
    }

    /**
     * @throws PersistenceException
     * @throws RefreshTokenException
     */
    public function findByToken(Token $token, ?bool $throwException = false): ?RefreshToken
    {
        $refreshToken = $this->refreshTokenRepository->findOneBy([new QueryCriteria(RefreshTokenRepository::FIELD_TOKEN, $token)]);
        if ($throwException && $refreshToken === null) {
            throw new RefreshTokenException('Invalid refresh token');
        }

        return $refreshToken;
    }

}
