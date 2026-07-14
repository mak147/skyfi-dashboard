<?php

declare(strict_types=1);

namespace SkyFi\Shared\Auth\Repositories;

use PDO;
use SkyFi\Shared\Auth\Contracts\RefreshTokenRepositoryContract;
use SkyFi\Shared\Auth\Models\RefreshToken;

final class PdoRefreshTokenRepository implements RefreshTokenRepositoryContract
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function create(int $userId, string $tokenHash, string $userAgent, string $ipAddress, string $expiresAt): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO refresh_tokens (user_id, token_hash, user_agent, ip_address, expires_at, created_at, updated_at)\n             VALUES (:user_id, :token_hash, :user_agent, :ip_address, :expires_at, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
        );
        $statement->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'user_agent' => substr($userAgent, 0, 65535),
            'ip_address' => $ipAddress,
            'expires_at' => $expiresAt,
        ]);
    }

    public function findValidByHash(string $tokenHash): ?RefreshToken
    {
        $statement = $this->connection->prepare(
            'SELECT id, user_id, token_hash, expires_at, used_at FROM refresh_tokens\n             WHERE token_hash = :token_hash AND used_at IS NULL AND revoked_at IS NULL AND expires_at > CURRENT_TIMESTAMP\n             LIMIT 1',
        );
        $statement->execute(['token_hash' => $tokenHash]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return null;
        }

        return new RefreshToken(
            (int) $row['id'],
            (int) $row['user_id'],
            (string) $row['token_hash'],
            (string) $row['expires_at'],
            $row['used_at'] !== null ? (string) $row['used_at'] : null,
        );
    }

    public function consume(int $id): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE refresh_tokens SET used_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP\n             WHERE id = :id AND used_at IS NULL AND revoked_at IS NULL AND expires_at > CURRENT_TIMESTAMP',
        );
        $statement->execute(['id' => $id]);

        return $statement->rowCount() === 1;
    }

    public function revokeByHash(string $tokenHash): void
    {
        $statement = $this->connection->prepare(
            'UPDATE refresh_tokens SET revoked_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP\n             WHERE token_hash = :token_hash AND revoked_at IS NULL',
        );
        $statement->execute(['token_hash' => $tokenHash]);
    }
}
