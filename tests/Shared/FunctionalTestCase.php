<?php

declare(strict_types=1);

namespace Tests\Shared;

use Inquisition\Core\Infrastructure\Http\HttpMethod;
use Inquisition\Core\Infrastructure\Http\Request\HttpRequest;
use Inquisition\Core\Infrastructure\Http\Response\HttpResponse;
use Inquisition\Core\Infrastructure\Http\Router\Exception\RouteNotFoundException;
use Inquisition\Core\Infrastructure\Http\Router\RequestDispatcher;
use InvalidArgumentException;

class FunctionalTestCase extends AbstractTestCase
{
    protected RequestDispatcher $dispatcher;

    #[\Override]
    public function setUp(): void
    {
        $this->dispatcher = RequestDispatcher::getInstance();

        parent::setUp();
    }

    protected function buildUri(
        string $path,
        array  $pathParams = [],
        array  $queryParams = [],
    ): string {
        foreach ($pathParams as $param => $value) {
            $path = preg_replace(
                '~\{' . preg_quote($param, '~') . '(?:<[^>]+>)?}~',
                (string) $value,
                $path,
                1,
            );
        }
        if (is_null($path)) {
            throw new InvalidArgumentException('Invalid path');
        }

        if (empty($queryParams)) {
            return $path;
        }

        return $path . '?' . http_build_query($queryParams);
    }

    /**
     * @throws RouteNotFoundException
     */
    protected function sendRequest(
        HttpMethod $method,
        string     $uri,
        array      $body = [],
        string     $rawBody = '',
        array      $files = [],
        string     $clientIp = '0.0.0.0',
        array      $headers = [],
        array      $query = [],
    ): HttpResponse {
        if (empty($query)) {
            parse_str(parse_url($uri, PHP_URL_QUERY) ?? '', $query);
        }

        $request = new HttpRequest(
            method: $method,
            uri: $uri,
            query: $query,
            body: $body,
            rawBody: $rawBody,
            files: $files,
            clientIp: $clientIp,
            headers: $headers,
        );

        return $this->dispatcher->handle($request);
    }

    /**
     * @throws RouteNotFoundException
     */
    protected function get(
        string  $uri,
        ?string $clientIp = null,
        ?array  $headers = null,
    ): HttpResponse {
        return $this->sendRequest(
            method: HttpMethod::GET,
            uri: $uri,
            clientIp: $clientIp,
            headers: $headers,
        );
    }

    /**
     * @throws RouteNotFoundException
     */
    protected function post(
        string  $uri,
        ?array  $body = [],
        ?string $rawBody = null,
        ?array  $files = null,
        ?string $clientIp = null,
        ?array  $headers = null,
    ): HttpResponse {
        return $this->sendRequest(
            method: HttpMethod::POST,
            uri: $uri,
            body: $body,
            rawBody: $rawBody,
            files: $files,
            clientIp: $clientIp,
            headers: $headers,
        );
    }

    /**
     * @throws RouteNotFoundException
     */
    protected function put(
        string  $uri,
        ?array  $body = [],
        ?string $rawBody = null,
        ?array  $files = null,
        ?string $clientIp = null,
        ?array  $headers = null,
    ): HttpResponse {
        return $this->sendRequest(
            method: HttpMethod::PUT,
            uri: $uri,
            body: $body,
            rawBody: $rawBody,
            files: $files,
            clientIp: $clientIp,
            headers: $headers,
        );
    }

    /**
     * @throws RouteNotFoundException
     */
    protected function patch(
        string  $uri,
        ?array  $body = [],
        ?string $rawBody = null,
        ?array  $files = null,
        ?string $clientIp = null,
        ?array  $headers = null,
    ): HttpResponse {
        return $this->sendRequest(
            method: HttpMethod::PATCH,
            uri: $uri,
            body: $body,
            rawBody: $rawBody,
            files: $files,
            clientIp: $clientIp,
            headers: $headers,
        );
    }

    /**
     * @throws RouteNotFoundException
     */
    protected function delete(
        string  $uri,
        ?string $clientIp = null,
        ?array  $headers = null,
    ): HttpResponse {
        return $this->sendRequest(
            method: HttpMethod::DELETE,
            uri: $uri,
            clientIp: $clientIp,
            headers: $headers,
        );
    }

    /**
     * @param string[] $routePath
     */
    protected function buildRouteName(array $routePath, string $action): string
    {
        return implode('.', $routePath) . '->' . $action;
    }
}
