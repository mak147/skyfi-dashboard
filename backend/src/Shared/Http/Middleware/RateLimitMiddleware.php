<?php

declare(strict_types=1);

namespace SkyFi\Shared\Http\Middleware;

use PDO;
use SkyFi\Shared\Exceptions\AppException;
use SkyFi\Shared\Http\Request;

/**
 * Per-IP rate limiter using a sliding minute window in the database.
 *
 * Wire this middleware before route handlers that are susceptible to abuse,
 * such as authentication endpoints.
 */
final class RateLimitMiddleware
{
    /**
     * @param PDO $pdo Database connection.
     * @param int $maxRequests Maximum allowed requests within the window.
     * @param int $decaySeconds Window duration in seconds (minimum 60).
     */
    public function __construct(
        private readonly PDO $pdo,
        private readonly int $maxRequests = 20,
        private readonly int $decaySeconds = 60,
    ) {
    }

    /**
     * Checks whether the request is within the rate limit.
     *
     * @throws AppException When the rate limit has been exceeded.
     */
    public function check(Request $request): void
    {
        $identifier = $this->resolveIdentifier($request);
        $window = gmdate('Y-m-d H:i');

        $stmt = $this->pdo->prepare(
            'SELECT count FROM api_rate_limits WHERE identifier = :id AND window = :window LIMIT 1'
        );
        $stmt->execute(['id' => $identifier, 'window' => $window]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $count = (int) ($row['count'] ?? 0);

        if ($count >= $this->maxRequests) {
            throw new AppException(
                'Too many requests. Please try again later.',
                429,
                'rate_limit_exceeded',
            );
        }

        if ($row !== false) {
            $this->pdo->prepare(
                'UPDATE api_rate_limits SET count = count + 1 WHERE identifier = :id AND window = :window'
            )->execute(['id' => $identifier, 'window' => $window]);
        } else {
            $this->pdo->prepare(
                'INSERT INTO api_rate_limits (identifier, window, count) VALUES (:id, :window, 1)'
            )->execute(['id' => $identifier, 'window' => $window]);
        }
    }

    private function resolveIdentifier(Request $request): string
    {
        // Use the authenticated user ID if available, otherwise fall back to IP
        $claims = $request->attributes()['claims'] ?? null;
        if (isset($claims['sub']) && is_string($claims['sub'])) {
            return 'user:' . $claims['sub'];
        }

        return 'ip:' . $request->ipAddress();
    }
}
