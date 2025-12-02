<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use Fetch\Enum\ContentType;
use GuzzleHttp\Cookie\CookieJarInterface;

interface RequestConfigurator
{
    public function withToken(string $token): self;

    public function withAuth(string $username, string $password): self;

    /**
     * @param array<string, mixed> $headers
     */
    public function withHeaders(array $headers): self;

    public function withHeader(string $header, mixed $value): self;

    /**
     * @param array<string, mixed>|string $body
     */
    public function withBody(array|string $body, string|ContentType $contentType = ContentType::JSON): self;

    /**
     * @param array<string, mixed> $data
     */
    public function withJson(array $data, int $options = 0): self;

    /**
     * @param array<string, mixed> $params
     */
    public function withFormParams(array $params): self;

    /**
     * @param array<int, array{name: string, contents: mixed, headers?: array<string, string>}> $multipart
     */
    public function withMultipart(array $multipart): self;

    /**
     * @param array<string, mixed> $queryParams
     */
    public function withQueryParameters(array $queryParams): self;

    public function withQueryParameter(string $name, mixed $value): self;

    public function timeout(int $seconds): self;

    /**
     * @param string|array<string, mixed> $proxy
     */
    public function withProxy(string|array $proxy): self;

    public function withCookies(bool|CookieJarInterface $cookies): self;

    /**
     * @param bool|array<string, mixed> $redirects
     */
    public function withRedirects(bool|array $redirects = true): self;

    /**
     * @param string|array<string, mixed> $cert
     */
    public function withCert(string|array $cert): self;

    /**
     * @param string|array<string, mixed> $sslKey
     */
    public function withSslKey(string|array $sslKey): self;

    public function withStream(bool $stream): self;

    /**
     * @param array<string, mixed> $options
     */
    public function withOptions(array $options): ClientHandler;

    public function withOption(string $key, mixed $value): self;

    public function baseUri(string $baseUri): self;

    /**
     * @param array<string, mixed> $options
     */
    public function withClonedOptions(array $options): self;

    public function reset(): self;

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array;

    /**
     * @return array<string, mixed>
     */
    public function getHeaders(): array;

    public function hasHeader(string $header): bool;

    public function hasOption(string $option): bool;
}
