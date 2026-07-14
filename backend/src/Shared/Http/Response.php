<?php

declare(strict_types=1);

namespace SkyFi\Shared\Http;

final class Response
{
    /** @var array<string, string> */
    private array $headers = [];

    /** @var array<int, array{name: string, value: string, options: array<string, mixed>}> */
    private array $cookies = [];

    /**
     * @param array<string, mixed>|null $payload JSON payload.
     */
    public function __construct(
        private readonly int $statusCode,
        private readonly ?array $payload = null,
        private readonly ?string $rawBody = null,
    ) {
        $this->headers['Content-Type'] = 'application/json; charset=utf-8';
    }

    /** @param array<string, mixed>|null $payload */
    public static function json(?array $payload = null, int $statusCode = 200): self
    {
        return new self($statusCode, $payload);
    }

    /** @param array<int, array<int, string>> $rows */
    public static function downloadCsv(array $rows, string $filename): self
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new \RuntimeException('Unable to create export.');
        }
        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return (new self(200, null, $csv === false ? '' : $csv))->withHeaders([
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '', $filename) . '"',
        ]);
    }

    /** @param array<string, string> $headers */
    public function withHeaders(array $headers): self
    {
        $response = clone $this;
        $response->headers = [...$this->headers, ...$headers];

        return $response;
    }

    /**
     * Adds an HttpOnly refresh-token cookie. The token value is never returned
     * in JSON or exposed to frontend JavaScript.
     *
     * @param string $name Cookie name.
     * @param string $value Cookie value.
     * @param int $expiresAt Unix expiry timestamp.
     * @param string $path Cookie path.
     * @param bool $secure Whether to require HTTPS.
     */
    public function withRefreshCookie(
        string $name,
        string $value,
        int $expiresAt,
        string $path,
        bool $secure,
    ): self {
        $response = clone $this;
        $response->cookies[] = [
            'name' => $name,
            'value' => $value,
            'options' => [
                'expires' => $expiresAt,
                'path' => $path,
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Strict',
            ],
        ];

        return $response;
    }

    /** @param string $name Cookie name. */
    public function withClearedCookie(string $name, string $path, bool $secure): self
    {
        return $this->withRefreshCookie($name, '', time() - 3600, $path, $secure);
    }

    /** Sends the response to the PHP runtime. */
    public function send(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }
        foreach ($this->cookies as $cookie) {
            setcookie($cookie['name'], $cookie['value'], $cookie['options']);
        }

        if ($this->statusCode !== 204 && $this->rawBody !== null) {
            echo $this->rawBody;
        } elseif ($this->statusCode !== 204 && $this->payload !== null) {
            echo json_encode($this->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        }
    }

    /** @return int HTTP status code. */
    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /** @return array<string, mixed>|null Payload. */
    public function payload(): ?array
    {
        return $this->payload;
    }
}
