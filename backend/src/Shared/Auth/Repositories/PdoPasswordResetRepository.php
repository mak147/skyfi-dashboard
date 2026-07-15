<?php

declare(strict_types=1);

namespace SkyFi\Shared\Auth\Repositories;

use PDO;
use SkyFi\Shared\Auth\Contracts\PasswordResetRepositoryContract;

final class PdoPasswordResetRepository implements PasswordResetRepositoryContract
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function create(int $userId, string $tokenHash, string $expiresAt): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (:user_id, :token_hash, :expires_at, CURRENT_TIMESTAMP)',
        );
        $statement->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function findValidUserId(string $tokenHash): ?int
    {
        $statement = $this->connection->prepare(
            'SELECT id, user_id FROM password_resets WHERE token_hash = :token_hash AND used_at IS NULL AND expires_at > CURRENT_TIMESTAMP LIMIT 1',
        );
        $statement->execute(['token_hash' => $tokenHash]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return (int) $row['user_id'];
    }

    public function markUsed(int $tokenId): void
    {
        $statement = $this->connection->prepare(
            'UPDATE password_resets SET used_at = CURRENT_TIMESTAMP WHERE id = :id',
        );
        $statement->execute(['id' => $tokenId]);
    }

    public function revokeForUser(int $userId): void
    {
        $statement = $this->connection->prepare(
            'UPDATE password_resets SET used_at = CURRENT_TIMESTAMP WHERE user_id = :user_id AND used_at IS NULL',
        );
        $statement->execute(['user_id' => $userId]);
    }

    public function pruneExpired(): int
    {
        $statement = $this->connection->prepare(
            'DELETE FROM password_resets WHERE expires_at <= CURRENT_TIMESTAMP OR used_at IS NOT NULL',
        );
        $statement->execute();

        return $statement->rowCount();
    }
}
