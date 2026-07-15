<?php

declare(strict_types=1);

namespace SkyFi\Shared\Auth\Services;

use SkyFi\Shared\Auth\Contracts\AuthServiceContract;
use SkyFi\Shared\Auth\Contracts\JwtServiceContract;
use SkyFi\Shared\Auth\Contracts\PasswordResetRepositoryContract;
use SkyFi\Shared\Auth\Contracts\RefreshTokenRepositoryContract;
use SkyFi\Shared\Auth\Contracts\UserRepositoryContract;
use SkyFi\Shared\Auth\Data\AuthSession;
use SkyFi\Shared\Auth\Data\LoginData;
use SkyFi\Shared\Auth\Models\User;
use SkyFi\Shared\Exceptions\AuthenticationException;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class AuthService implements AuthServiceContract
{
    private const RESET_TOKEN_TTL_SECONDS = 3600;

    public function __construct(
        private readonly UserRepositoryContract $users,
        private readonly RefreshTokenRepositoryContract $refreshTokens,
        private readonly PasswordResetRepositoryContract $passwordResets,
        private readonly JwtServiceContract $jwt,
        private readonly \PDO $pdo,
        private readonly int $refreshTtl,
        private readonly int $sessionRefreshTtl,
    ) {
    }

    /**
     * Verifies credentials and creates a stateless access token plus a persisted
     * one-time refresh token.
     */
    public function login(LoginData $data, string $userAgent, string $ipAddress): AuthSession
    {
        $user = $this->users->findByEmail($data->email);
        if ($user === null || !password_verify($data->password, $user->passwordHash)) {
            throw new AuthenticationException();
        }

        return $this->createSession($user, $userAgent, $ipAddress, $data->rememberMe);
    }

    /**
     * Rotates a refresh token and returns a new access/refresh token pair.
     */
    public function refresh(string $rawRefreshToken, string $userAgent, string $ipAddress): AuthSession
    {
        $tokenHash = hash('sha256', $rawRefreshToken);
        $storedToken = $this->refreshTokens->findValidByHash($tokenHash);
        if ($storedToken === null || $storedToken->isExpired() || !$this->refreshTokens->consume($storedToken->id)) {
            throw new AuthenticationException('The refresh token is invalid or expired.', 'refresh_token_invalid');
        }

        $user = $this->users->findById($storedToken->userId);
        if ($user === null) {
            throw new AuthenticationException('The refresh token is invalid or expired.', 'refresh_token_invalid');
        }

        $remainingLifetime = strtotime($storedToken->expiresAt) - time();
        $rememberMe = $remainingLifetime > $this->sessionRefreshTtl;

        return $this->createSession($user, $userAgent, $ipAddress, $rememberMe);
    }

    /** Invalidates the current refresh token, if one was provided. */
    public function logout(?string $rawRefreshToken): void
    {
        if ($rawRefreshToken === null || $rawRefreshToken === '') {
            return;
        }

        $this->refreshTokens->revokeByHash(hash('sha256', $rawRefreshToken));
    }

    private function createSession(User $user, string $userAgent, string $ipAddress, bool $rememberMe): AuthSession
    {
        $refreshToken = self::generateRefreshToken();
        $ttl = $rememberMe ? $this->refreshTtl : $this->sessionRefreshTtl;
        $refreshExpiresAt = time() + $ttl;
        $expiresAt = gmdate('Y-m-d H:i:s', $refreshExpiresAt);

        $this->refreshTokens->create(
            $user->id,
            hash('sha256', $refreshToken),
            $userAgent,
            $ipAddress,
            $expiresAt,
        );

        return new AuthSession($user, $this->jwt->issue($user), $refreshToken, $refreshExpiresAt);
    }

    /** Generates at least 64 cryptographically random characters. */
    private static function generateRefreshToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=');
    }

    public function forgotPassword(string $email): string
    {
        $user = $this->users->findByEmail($email);
        if ($user === null) {
            // Do not reveal whether the email exists.
            return '';
        }

        $this->passwordResets->revokeForUser($user->id);

        $token = self::generateResetToken();
        $expiresAt = gmdate('Y-m-d H:i:s', time() + self::RESET_TOKEN_TTL_SECONDS);
        $this->passwordResets->create($user->id, hash('sha256', $token), $expiresAt);

        return $token;
    }

    public function resetPassword(string $rawToken, string $newPassword): void
    {
        if (strlen($newPassword) < 8) {
            throw new ValidationException([
                ['code' => 'min_length', 'detail' => 'Password must be at least 8 characters long.', 'source' => ['pointer' => '/data/attributes/password']],
            ]);
        }

        $userId = $this->passwordResets->findValidUserId(hash('sha256', $rawToken));
        if ($userId === null) {
            throw new ValidationException([
                ['code' => 'invalid_token', 'detail' => 'The reset token is invalid or expired.', 'source' => ['pointer' => '/data/attributes/token']],
            ]);
        }

        $this->updatePassword($userId, $newPassword);
        $this->passwordResets->revokeForUser($userId);
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): void
    {
        $user = $this->users->findById($userId);
        if ($user === null || !password_verify($currentPassword, $user->passwordHash)) {
            throw new AuthenticationException('The current password is incorrect.', 'invalid_credentials');
        }

        if (strlen($newPassword) < 8) {
            throw new ValidationException([
                ['code' => 'min_length', 'detail' => 'Password must be at least 8 characters long.', 'source' => ['pointer' => '/data/attributes/new_password']],
            ]);
        }

        $this->updatePassword($userId, $newPassword);
    }

    private function updatePassword(int $userId, string $newPassword): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE users SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :id',
        );
        $statement->execute([
            'id' => $userId,
            'password' => password_hash($newPassword, PASSWORD_ARGON2ID),
        ]);
    }

    private static function generateResetToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
    }
}
