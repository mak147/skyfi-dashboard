<?php

declare(strict_types=1);

namespace SkyFi\Shared\Logging;

use Throwable;

final class JsonLogger
{
    public function __construct(
        private readonly string $path,
        private readonly string $service = 'skyfi-api',
    ) {
        $directory = dirname($this->path);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
    }

    /**
     * Writes one structured JSON object per line and strips sensitive keys.
     *
     * @param string $level RFC 5424-compatible lowercase level.
     * @param string $message Human-readable event description.
     * @param array<string, mixed> $context Event context.
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $entry = [
            'timestamp' => gmdate('c'),
            'level' => $level,
            'message' => $message,
            'service' => $this->service,
            'hostname' => gethostname() ?: 'unknown',
            'trace_id' => $context['trace_id'] ?? bin2hex(random_bytes(16)),
            ...$this->scrub($context),
        ];

        file_put_contents(
            $this->path,
            json_encode($entry, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND | LOCK_EX,
        );
    }

    /** Logs a programmer error with its stack trace. */
    public function exception(Throwable $exception, string $traceId, array $context = []): void
    {
        $this->log('critical', 'Unhandled application exception.', [
            ...$context,
            'trace_id' => $traceId,
            'exception' => $exception::class,
            'stack_trace' => $exception->getTraceAsString(),
        ]);
    }

    /** @param mixed $value @return mixed */
    private function scrub(mixed $value, ?string $key = null): mixed
    {
        $sensitive = ['password', 'password_hash', 'token', 'access_token', 'refresh_token', 'secret', 'api_key'];
        if ($key !== null && in_array(strtolower($key), $sensitive, true)) {
            return '[REDACTED]';
        }
        if (!is_array($value)) {
            return $value;
        }

        $clean = [];
        foreach ($value as $childKey => $childValue) {
            $clean[$childKey] = $this->scrub($childValue, is_string($childKey) ? $childKey : null);
        }

        return $clean;
    }
}
