<?php

declare(strict_types=1);

namespace SkyFi\Shared\Auth\Contracts;

interface PasswordResetRepositoryContract
{
    /** Creates a reset token record and returns its database ID. */
    public function create(int $userId, string $tokenHash, string $expiresAt): int;

    /** Finds a valid, unused, non-expired token by its hash. Returns user_id or null. */
    public function findValidUserId(string $tokenHash): ?int;

    /** Marks a token as used. */
    public function markUsed(int $tokenId): void;

    /** Revokes all valid tokens for a user. */
    public function revokeForUser(int $userId): void;

    /** Deletes expired tokens. */
    public function pruneExpired(): int;
}
