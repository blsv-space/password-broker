<?php

namespace App\Module\Identity\Domain\User\Service;

use App\Module\Identity\Domain\RefreshToken\Entity\RefreshToken;
use App\Module\Identity\Domain\RefreshToken\Service\Exception\RefreshTokenException;
use App\Module\Identity\Domain\RefreshToken\Service\RefreshTokenService;
use App\Module\Identity\Domain\RefreshToken\ValueObject\Token;
use App\Module\Identity\Domain\User\DTO\JwtTokenPayloadDTO;
use App\Module\Identity\Domain\User\DTO\LoginResponseDTO;
use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\Identity\Infrastructure\Security\PasswordHasher;
use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;
use App\Shared\Infrastructure\Security\Exception\JwtTokenExpiredException;
use App\Shared\Infrastructure\Security\JwtAlgoEnum;
use App\Shared\Infrastructure\Security\JwtTokenGenerator;
use DateInterval;
use DateMalformedIntervalStringException;
use Exception;
use Inquisition\Core\Domain\Service\DomainServiceInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Foundation\Config\Config;
use Inquisition\Foundation\Singleton\SingletonTrait;
use RuntimeException;

final class AuthDomainService
    implements DomainServiceInterface
{
    use SingletonTrait;

    private const string JWT_TTL_DEFAULT = '1 day';
    private const string REFRESH_TTL_DEFAULT = '30 days';

    private PasswordHasher $passwordHasher;
    private JwtTokenGenerator $jwtTokenGenerator;
    private RefreshTokenService $refreshTokenService;
    private UserDomainService $userDomainService;
    private Config $config;

    public function __construct()
    {
        $this->passwordHasher = PasswordHasher::getInstance();
        $this->jwtTokenGenerator = JwtTokenGenerator::getInstance();
        $this->refreshTokenService = RefreshTokenService::getInstance();
        $this->userDomainService = UserDomainService::getInstance();
        $this->config = Config::getInstance();
    }

    /**
     * @param string $password
     *
     * @return string
     */
    public function hashPassword(string $password): string
    {
        return $this->passwordHasher->hash($password);
    }

    /**
     * @param User $user
     * @param string $password
     *
     * @return bool
     */
    public function verifyPasswordByUser(User $user, string $password): bool
    {
        return $this->verifyPasswordHash(
            passwordHash: $user->hashedPassword->toRaw(),
            password: $password,
        );
    }

    /**
     * @param string $passwordHash
     * @param string $password
     * @return bool
     */
    public function verifyPasswordHash(string $passwordHash, string $password): bool
    {
        return $this->passwordHasher->verify(
            plain: $password,
            hashed: $passwordHash,
        );
    }

    /**
     * @param User $user
     * @return LoginResponseDTO
     * @throws PersistenceException
     */
    public function login(User $user): LoginResponseDTO
    {
        $jwtTokenPayload = new JwtTokenPayloadDTO(
            userId: $user->id,
        );

        $jwtSecret = $this->getJwtSecret();
        $jwtTtl = $this->getJwtTtl();
        $jwtAlgorithm = $this->getJwtAlgorithm();

        $jwtToken = $this->jwtTokenGenerator->generate(
            secret: $jwtSecret,
            payload: $jwtTokenPayload->getAsArray(),
            ttl: $jwtTtl,
            algoEnum: $jwtAlgorithm,
        );

        try {
            $refreshTtl = DateInterval::createFromDateString($this->config->getByPath('security.refresh_token.time_to_live', self::REFRESH_TTL_DEFAULT));
        } catch (Exception $_) {
            throw new RuntimeException('Invalid time to live format. Should be set in config in security.refresh_token.time_to_live.');
        }

        $refreshToken = $this->refreshTokenService->createRefreshToken(
            userId: $user->id,
            expiresIn: $refreshTtl,
        );

        return new LoginResponseDTO(
            jwtToken: $jwtToken,
            refreshToken: $refreshToken->token->toRaw(),
        );
    }

    /**
     * @param User $user
     * @return void
     * @throws PersistenceException
     */
    public function logout(User $user): void
    {
        $this->refreshTokenService->removeRefreshTokenByUser($user);
    }

    /**
     * @param Token $token
     * @return LoginResponseDTO
     * @throws PersistenceException
     * @throws RefreshTokenException
     */
    public function refreshToken(Token $token): LoginResponseDTO
    {
        $refreshToken = $this->refreshTokenService->findByToken($token, true);

        $this->refreshTokenService->refreshTokenValidate($refreshToken);

        $user = $this->userDomainService->findUserById($refreshToken->userId);

        if (!$user) {
            throw new RefreshTokenException('User not found.');
        }

        $loginResponseDTO = $this->login($user);

        $this->refreshTokenService->removeRefreshToken($token);

        return $loginResponseDTO;
    }

    /**
     * @param string $token
     * @return User
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws PersistenceException
     */
    public function authByJwtToken(string $token): User
    {
        $payload = $this->jwtTokenGenerator->verify($token, $this->getJwtSecret());

        if (!array_key_exists('userId', $payload)) {
            throw new JwtInvalidTokenException('Invalid JWT token.');
        }
        $userID = UserId::fromRaw($payload['userId']);

        $user = $this->userDomainService->findUserById($userID);

        if (!$user) {
            throw new JwtInvalidTokenException('User not found.');
        }

        return $user;
    }

    /**
     * @return mixed
     */
    public function getJwtSecret(): mixed
    {
        $jwtSecret = $this->config->getByPath('security.jwt.secret');
        if (empty($jwtSecret)) {
            throw new RuntimeException('JWT secret is not set in security.jwt.secret.');
        }

        return $jwtSecret;
    }

    /**
     * @return DateInterval
     */
    public function getJwtTtl(): DateInterval
    {
        try {
            $jwtTtl = DateInterval::createFromDateString($this->config->getByPath('security.jwt.time_to_live', self::JWT_TTL_DEFAULT));
        } catch (Exception $_) {
            throw new RuntimeException('Invalid time to live format. Should be set in config in security.jwt.time_to_live.');
        }

        return $jwtTtl;
    }

    /**
     * @return JwtAlgoEnum
     */
    public function getJwtAlgorithm(): JwtAlgoEnum
    {
        $jwtAlgorithm = $this->config->getByPath('security.jwt.algo', 'sha256');
        $jwtAlgoEnum = JwtAlgoEnum::tryFrom($jwtAlgorithm);
        if (!$jwtAlgoEnum) {
            throw new RuntimeException('Invalid JWT algorithm. Should be set in config in security.jwt.algo.');
        }

        return $jwtAlgoEnum;
    }
}