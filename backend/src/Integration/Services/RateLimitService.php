<?php

declare(strict_types=1);

namespace SkyFi\Integration\Services;

use PDO;

/**
 * Simple rate limiter using database counters.
 */
final class RateLimitService
{
    public function __construct(private readonly PDO $pdo) {}

    /**
     * Check and increment rate limit for a given identifier.
     * Returns true if the request is within limits.
     */
    public function check(string $identifier, int $limitPerMinute): bool
    {
        $window = gmdate('Y-m-d H:i');

        $stmt = $this->pdo->prepare(
            "SELECT count FROM api_rate_limits WHERE identifier = :id AND window = :window LIMIT 1"
        );
        $stmt->execute(['id' => $identifier, 'window' => $window]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $count = (int) ($row['count'] ?? 0);
        if ($count >= $limitPerMinute) {
            return false;
        }

        if ($row) {
            $this->pdo->prepare(
                "UPDATE api_rate_limits SET count = count + 1 WHERE identifier = :id AND window = :window"
            )->execute(['id' => $identifier, 'window' => $window]);
        } else {
            $this->pdo->prepare(
                "INSERT INTO api_rate_limits (identifier, window, count) VALUES (:id, :window, 1)"
            )->execute(['id' => $identifier, 'window' => $window]);
        }

        return true;
    }
}
