<?php

declare(strict_types=1);

namespace App\Module\Identity\Application\User\Service;

use App\Module\Identity\Application\User\Service\Exception\AuthInvalidPasswordException;
use App\Module\Identity\Application\User\Service\Exception\AuthUserNotFoundException;
use App\Module\Identity\Application\User\Service\Exception\RefreshTokenException;
use App\Module\Identity\Domain\RefreshToken\Service\Exception\RefreshTokenDomainException;
use App\Module\Identity\Domain\RefreshToken\Service\RefreshTokenDomainService;
use App\Module\Identity\Domain\RefreshToken\ValueObject\Token;
use App\Module\Identity\Domain\User\DTO\JwtTokenPayloadDto;
use App\Module\Identity\Domain\User\DTO\LoginResponseDto;
use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Domain\User\Service\AuthDomainService;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\Identity\Domain\User\ValueObject\UserName;
use App\Module\Identity\Infrastructure\User\Repository\UserRepository;
use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;
use App\Shared\Infrastructure\Security\Exception\JwtTokenExpiredException;
use App\Shared\Infrastructure\Security\JwtConfigProvider;
use App\Shared\Infrastructure\Security\JwtTokenGenerator;
use Inquisition\Core\Application\Service\ApplicationServiceInterface;
use Inquisition\Core\Infrastructure\Http\Router\RequestDispatcher;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Foundation\Config\Config;
use Inquisition\Foundation\Singleton\SingletonTrait;

class AuthApplicationService implements ApplicationServiceInterface
{
    use SingletonTrait;

    private const string REFRESH_TTL_DEFAULT = '30 days';

    private AuthDomainService $authDomainService;
    private UserRepository $userRepository;
    private RefreshTokenApplicationService $refreshTokenApplicationService;
    private RefreshTokenDomainService $refreshTokenDomainService;
    private RequestDispatcher $requestDispatcher;
    private JwtConfigProvider $jwtConfigProvider;
    private JwtTokenGenerator $jwtTokenGenerator;
    private Config $config;
    private ?User $authUser = null;

    public function __construct()
    {
        $this->authDomainService = AuthDomainService::getInstance();
        $this->userRepository = UserRepository::getInstance();
        $this->refreshTokenApplicationService = RefreshTokenApplicationService::getInstance();
        $this->refreshTokenDomainService = RefreshTokenDomainService::getInstance();
        $this->requestDispatcher = RequestDispatcher::getInstance();
        $this->jwtConfigProvider = JwtConfigProvider::getInstance();
        $this->jwtTokenGenerator = JwtTokenGenerator::getInstance();
        $this->config = Config::getInstance();
    }

    /**
     * @throws AuthInvalidPasswordException
     * @throws AuthUserNotFoundException
     * @throws PersistenceException
     */
    public function login(string $username, string $password): LoginResponseDto
    {
        $userName = UserName::fromRaw($username);
        $user = $this->userRepository->findByUsername($userName);
        if (!$user) {
            throw new AuthUserNotFoundException($userName->toRaw());
        }

        if (!$this->authDomainService->verifyPasswordByUser($user, $password)) {
            throw new AuthInvalidPasswordException($userName->toRaw());
        }

        return $this->auth($user);
    }

    /**
     * @throws PersistenceException
     */
    public function auth(User $user): LoginResponseDto
    {
        $jwtTokenPayload = new JwtTokenPayloadDto(
            userId: $user->id,
        );

        $jwtConfig = $this->jwtConfigProvider->getConfig();

        $jwtToken = $this->jwtTokenGenerator->generateByJwtConfig(
            jwtConfig: $jwtConfig,
            payload: $jwtTokenPayload->getAsArray(),
        );

        $refreshToken = $this->refreshTokenApplicationService->createRefreshToken(
            userId: $user->id,
            expiresIn: $jwtConfig->ttl,
        );

        return new LoginResponseDto(
            jwtToken: $jwtToken,
            refreshToken: $refreshToken->token->toRaw(),
        );
    }

    /**
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws PersistenceException
     */
    public function authByJwtToken(string $token): User
    {
        $jwtConfig = $this->jwtConfigProvider->getConfig();
        $payload = $this->jwtTokenGenerator->verify($token, $jwtConfig->secret);

        if (!array_key_exists('userId', $payload)) {
            throw new JwtInvalidTokenException('Invalid JWT token.');
        }
        $userID = UserId::fromRaw($payload['userId']);

        $user = $this->userRepository->findById($userID);

        if (!$user) {
            throw new JwtInvalidTokenException('User not found.');
        }

        return $user;
    }

    /**
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws PersistenceException
     */
    public function logout(?User $user = null): void
    {
        $user = $user ?? $this->authUser();
        if (!$user) {
            return;
        }

        $this->refreshTokenApplicationService->removeRefreshTokenByUser($user);
        $this->authUser = null;
    }

    /**
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     * @throws PersistenceException
     */
    public function authUser(?string $token = null, ?bool $disableCache = false): ?User
    {
        if ($this->authUser && !$disableCache) {
            return $this->authUser;
        }

        $token = $token ?? $this->extractTokenFromRequest();

        if (!$token) {
            return null;
        }

        $this->authUser = $this->authByJwtToken($token);

        try {
            $this->authUser = $this->authByJwtToken($token);
        } catch (JwtInvalidTokenException|JwtTokenExpiredException $_) {
            return null;
        }

        return $this->authUser;
    }

    /**
     * Extract JWT token from current request
     *
     */
    private function extractTokenFromRequest(): ?Token
    {
        $request = $this->requestDispatcher->request;
        if (!$request) {
            return null;
        }
        $authHeader = $request->getHeader('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        return Token::fromRaw(str_replace('Bearer ', '', $authHeader));
    }


    /**
     * @throws PersistenceException
     * @throws RefreshTokenException
     * @throws RefreshTokenDomainException
     */
    public function refreshToken(Token $token): LoginResponseDto
    {
        $refreshToken = $this->refreshTokenApplicationService->findByToken($token, true);

        $this->refreshTokenDomainService->refreshTokenValidate($refreshToken);

        $user = $this->userRepository->findById($refreshToken->userId);

        if (!$user) {
            throw new RefreshTokenDomainException('User not found.');
        }

        $loginResponseDto = $this->auth($user);

        $this->refreshTokenApplicationService->removeRefreshToken($token);

        return $loginResponseDto;
    }
}
