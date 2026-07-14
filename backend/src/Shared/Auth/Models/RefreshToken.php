<?php

declare(strict_types=1);

namespace SkyFi\Shared\Auth\Models;

final class RefreshToken
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $tokenHash,
        public readonly string $expiresAt,
        public readonly ?string $usedAt = null,
    ) {
    }

    /** Returns whether the database timestamp has passed. */
    public function isExpired(): bool
    {
        return strtotime($this->expiresAt) <= time();
    }

    /** Returns whether this single-use token has already been consumed. */
    public function isConsumed(): bool
    {
        return $this->usedAt !== null;
    }
}
