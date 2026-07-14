<?php

declare(strict_types=1);

namespace SkyFi\Shared\Auth\Contracts;

use SkyFi\Shared\Auth\Models\RefreshToken;

interface RefreshTokenRepositoryContract
{
    /** Stores a hashed, opaque refresh token. */
    public function create(int $userId, string $tokenHash, string $userAgent, string $ipAddress, string $expiresAt): void;

    /** Finds an unconsumed token that matches a hash. */
    public function findValidByHash(string $tokenHash): ?RefreshToken;

    /** Atomically consumes a token so it cannot be replayed. */
    public function consume(int $id): bool;

    /** Revokes a token during logout. */
    public function revokeByHash(string $tokenHash): void;
}
