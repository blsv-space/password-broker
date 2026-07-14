<?php

declare(strict_types=1);

namespace App\Module\Identity\Infrastructure\Http\Controller;

use App\Module\Identity\Application\User\Service\AuthApplicationService;
use App\Module\Identity\Application\User\Service\Exception\AuthInvalidPasswordException;
use App\Module\Identity\Application\User\Service\Exception\AuthUserNotFoundException;
use App\Module\Identity\Application\User\Service\Exception\RefreshTokenException;
use App\Module\Identity\Domain\RefreshToken\Service\Exception\RefreshTokenDomainException;
use App\Module\Identity\Domain\RefreshToken\ValueObject\Token;
use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;
use App\Shared\Infrastructure\Security\Exception\JwtTokenExpiredException;
use Inquisition\Core\Application\Validation\Exception\ValidationException;
use Inquisition\Core\Application\Validation\HttpRequestValidator;
use Inquisition\Core\Application\Validation\Rule\NotEmptyRule;
use Inquisition\Core\Infrastructure\Http\Controller\AbstractApiController;
use Inquisition\Core\Infrastructure\Http\HttpStatusCode;
use Inquisition\Core\Infrastructure\Http\Request\RequestInterface;
use Inquisition\Core\Infrastructure\Http\Response\ResponseInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use JsonException;

final readonly class AuthController extends AbstractApiController
{
    public const string ACTION_LOGIN = 'login';
    public const string ACTION_LOGOUT = 'logout';
    public const string ACTION_REFRESH_TOKEN = 'refreshToken';

    /**
     * @throws JsonException
     * @throws AuthInvalidPasswordException
     * @throws AuthUserNotFoundException
     * @throws ValidationException
     * @throws PersistenceException
     */
    public function login(RequestInterface $request, array $parameters): ResponseInterface
    {
        $httpRequestValidator = new HttpRequestValidator();
        $httpRequestValidator->addRules([
            'userName' => new NotEmptyRule(),
            'password' => new NotEmptyRule(),
        ]);

        $httpRequestValidator->validate($request);

        $requestParameters = $request->getAllParameters();
        $loginResponseDto = AuthApplicationService::getInstance()->login($requestParameters['userName'], $requestParameters['password']);

        return $this->jsonResponse($loginResponseDto->getAsArray());
    }

    /**
     * @throws JsonException
     * @throws PersistenceException
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     */
    public function logout(RequestInterface $request, array $parameters): ResponseInterface
    {
        AuthApplicationService::getInstance()->logout();

        return $this->jsonResponse(
            data: [],
            statusCode: HttpStatusCode::NO_CONTENT,
        );
    }

    /**
     * @throws JsonException
     * @throws PersistenceException
     * @throws RefreshTokenDomainException
     * @throws RefreshTokenException
     */
    public function refreshToken(RequestInterface $request, array $parameters): ResponseInterface
    {
        $httpRequestValidator = new HttpRequestValidator();
        $httpRequestValidator->addRules([
            'refreshToken' => new NotEmptyRule(),
        ]);

        $loginResponseDto = AuthApplicationService::getInstance()->refreshToken(
            token: Token::fromRaw($request->getParameter('refreshToken')),
        );

        return $this->jsonResponse($loginResponseDto->getAsArray());
    }
}
