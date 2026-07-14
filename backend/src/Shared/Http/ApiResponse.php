<?php

declare(strict_types=1);

namespace SkyFi\Shared\Http;

use SkyFi\Shared\Exceptions\AppException;

final class ApiResponse
{
    /**
     * Creates a JSON:API-inspired single resource response.
     *
     * @param string $type Resource type.
     * @param string $id Resource identifier.
     * @param array<string, mixed> $attributes Resource attributes.
     */
    public static function resource(string $type, string $id, array $attributes, int $statusCode = 200): Response
    {
        return new Response($statusCode, [
            'data' => [
                'type' => $type,
                'id' => $id,
                'attributes' => $attributes,
            ],
        ]);
    }

    /** Creates a no-content response. */
    public static function noContent(): Response
    {
        return new Response(204);
    }

    /**
     * Converts an expected or unexpected exception to the public API error contract.
     */
    public static function error(\Throwable $exception, string $traceId, bool $debug = false): Response
    {
        if ($exception instanceof AppException) {
            $errors = $exception->details();
            if ($errors === []) {
                $errors = [[
                    'status' => (string) $exception->statusCode(),
                    'code' => $exception->errorCode(),
                    'title' => self::titleForStatus($exception->statusCode()),
                    'detail' => $exception->getMessage(),
                ]];
            } else {
                $errors = array_map(
                    static fn (array $error): array => [
                        'status' => (string) $exception->statusCode(),
                        'code' => $error['code'] ?? $exception->errorCode(),
                        'title' => self::titleForStatus($exception->statusCode()),
                        'detail' => $error['detail'] ?? $exception->getMessage(),
                        ...(isset($error['source']) ? ['source' => $error['source']] : []),
                    ],
                    $errors,
                );
            }

            return new Response($exception->statusCode(), ['errors' => $errors]);
        }

        $error = [
            'status' => '500',
            'code' => 'internal_server_error',
            'title' => 'Internal Server Error',
            'detail' => 'An unexpected error occurred. Please reference the trace ID when contacting support.',
            'meta' => ['trace_id' => $traceId],
        ];
        if ($debug) {
            $error['meta']['exception'] = $exception::class;
            $error['meta']['message'] = $exception->getMessage();
        }

        return new Response(500, ['errors' => [$error]]);
    }

    private static function titleForStatus(int $statusCode): string
    {
        return match ($statusCode) {
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Unprocessable Entity',
            503 => 'Service Unavailable',
            default => 'Bad Request',
        };
    }
}
