<?php

namespace Tests\Shared;

use Inquisition\Core\Infrastructure\Http\HttpMethod;
use Inquisition\Core\Infrastructure\Http\Request\HttpRequest;
use Inquisition\Core\Infrastructure\Http\Response\HttpResponse;
use Inquisition\Core\Infrastructure\Http\Router\Exception\RouteNotFoundException;
use Inquisition\Core\Infrastructure\Http\Router\RequestDispatcher;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;

class FunctionalTestCase extends AbstractTestCase
{
    protected RequestDispatcher $dispatcher;

    /**
     * @return void
     * @throws PersistenceException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = RequestDispatcher::getInstance();
        $this->flushDatabase();
        $this->resetFixtures();
    }

    /**
     * @param string $path
     * @param array $pathParams
     * @param array $queryParams
     * @return string
     */
    protected function buildUri(
        string $path,
        array  $pathParams = [],
        array  $queryParams = [],
    ): string
    {
        foreach ($pathParams as $param => $value) {
            $path = preg_replace(
                '~\{' . preg_quote($param, '~') . '(?:<[^>]+>)?}~',
                $value,
                $path,
                1
            );
        }
        if (empty($queryParams)) {
            return $path;
        }

        return $path . '?' . http_build_query($queryParams);
    }

    /**
     * @param HttpMethod $method
     * @param string $uri
     * @param array $body
     * @param string $rawBody
     * @param array $files
     * @param string $clientIp
     * @param array $headers
     * @return HttpResponse
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
    ): HttpResponse
    {
        parse_str(parse_url($uri, PHP_URL_QUERY) ?? '', $query);

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
     * @param string $uri
     * @param string|null $clientIp
     * @param array|null $headers
     * @return HttpResponse
     * @throws RouteNotFoundException
     */
    protected function get(
        string  $uri,
        ?string $clientIp = null,
        ?array  $headers = null,
    ): HttpResponse
    {
        return $this->sendRequest(
            method: HttpMethod::GET,
            uri: $uri,
            clientIp: $clientIp,
            headers: $headers,
        );
    }

    /**
     * @param string $uri
     * @param array|null $body
     * @param string|null $rawBody
     * @param array|null $files
     * @param string|null $clientIp
     * @param array|null $headers
     * @return HttpResponse
     * @throws RouteNotFoundException
     */
    protected function post(
        string  $uri,
        ?array  $body = [],
        ?string $rawBody = null,
        ?array  $files = null,
        ?string $clientIp = null,
        ?array  $headers = null,
    ): HttpResponse
    {
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
     * @param string $uri
     * @param array|null $body
     * @param string|null $rawBody
     * @param array|null $files
     * @param string|null $clientIp
     * @param array|null $headers
     * @return HttpResponse
     * @throws RouteNotFoundException
     */
    protected function put(
        string  $uri,
        ?array  $body = [],
        ?string $rawBody = null,
        ?array  $files = null,
        ?string $clientIp = null,
        ?array  $headers = null,
    ): HttpResponse
    {
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
     * @param string $uri
     * @param array|null $body
     * @param string|null $rawBody
     * @param array|null $files
     * @param string|null $clientIp
     * @param array|null $headers
     * @return HttpResponse
     * @throws RouteNotFoundException
     */
    protected function patch(
        string  $uri,
        ?array  $body = [],
        ?string $rawBody = null,
        ?array  $files = null,
        ?string $clientIp = null,
        ?array  $headers = null,
    ): HttpResponse
    {
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
     * @param string $uri
     * @param string|null $clientIp
     * @param array|null $headers
     * @return HttpResponse
     * @throws RouteNotFoundException
     */
    protected function delete(
        string  $uri,
        ?string $clientIp = null,
        ?array  $headers = null,
    ): HttpResponse
    {
        return $this->sendRequest(
            method: HttpMethod::DELETE,
            uri: $uri,
            clientIp: $clientIp,
            headers: $headers,
        );
    }

    /**
     * @param string[] $routePath
     * @param string $action
     * @return string
     */
    protected function buildRouteName(array $routePath, string $action): string
    {
        return implode('.', $routePath) . '->' . $action;
    }
}