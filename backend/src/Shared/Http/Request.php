<?php

declare(strict_types=1);

namespace SkyFi\Shared\Http;

final class Request
{
    /** @var array<string, mixed> */
    private array $body;

    /** @var array<string, string> */
    private array $headers;

    /** @var array<string, mixed> */
    private array $cookies;

    /** @var array<string, mixed> */
    private array $server;

    /** @param array<string, mixed> $server @param array<string, mixed> $cookies */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        array $server,
        ?string $rawBody = null,
        array $cookies = [],
    ) {
        $this->server = $server;
        $this->cookies = $cookies;
        $this->headers = self::extractHeaders($server);
        $decoded = json_decode($rawBody ?? '', true);
        $this->body = is_array($decoded) ? $decoded : [];
    }

    /**
     * Builds a request from PHP's current request globals.
     */
    public static function fromGlobals(): self
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            is_string($path) ? $path : '/',
            $_SERVER,
            file_get_contents('php://input') ?: null,
            $_COOKIE,
        );
    }

    /** @return string HTTP method. */
    public function method(): string
    {
        return $this->method;
    }

    /** @return string Request path without query parameters. */
    public function path(): string
    {
        return $this->path;
    }

    /** @return array<string, mixed> Parsed JSON request body. */
    public function body(): array
    {
        return $this->body;
    }

    /** @param string $name Header name. */
    public function header(string $name): ?string
    {
        $normalized = strtolower($name);

        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $normalized) {
                return $value;
            }
        }

        return null;
    }

    /** @return string|null Authorization header. */
    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization');
        if ($header === null || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /** @return string|null Refresh token cookie. */
    public function refreshToken(string $cookieName): ?string
    {
        $token = $this->cookies[$cookieName] ?? null;

        return is_string($token) && $token !== '' ? $token : null;
    }

    /** @return string Client IP address. */
    public function ipAddress(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    /** @return string User-Agent header. */
    public function userAgent(): string
    {
        return $this->header('User-Agent') ?? 'unknown';
    }

    /** @param array<string, mixed> $server @return array<string, string> */
    private static function extractHeaders(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_') && is_string($value)) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }

        if (isset($server['CONTENT_TYPE']) && is_string($server['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $server['CONTENT_TYPE'];
        }

        return $headers;
    }
}
