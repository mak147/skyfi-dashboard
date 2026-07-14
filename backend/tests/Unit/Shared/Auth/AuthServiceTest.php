<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Shared\Auth;

use PHPUnit\Framework\TestCase;
use SkyFi\Shared\Auth\Contracts\JwtServiceContract;
use SkyFi\Shared\Auth\Contracts\RefreshTokenRepositoryContract;
use SkyFi\Shared\Auth\Contracts\UserRepositoryContract;
use SkyFi\Shared\Auth\Data\LoginData;
use SkyFi\Shared\Auth\Models\RefreshToken;
use SkyFi\Shared\Auth\Models\User;
use SkyFi\Shared\Auth\Services\AuthService;
use SkyFi\Shared\Exceptions\AuthenticationException;

final class AuthServiceTest extends TestCase
{
    public function testLoginPersistsOnlyTheRefreshTokenHash(): void
    {
        $user = new User(7, 'Ada Lovelace', 'ada@example.com', password_hash('correct-password', PASSWORD_ARGON2ID), ['Customer Support']);
        $users = new InMemoryUsers($user);
        $refreshTokens = new InMemoryRefreshTokens();
        $service = new AuthService($users, $refreshTokens, new FakeJwt(), 2_592_000, 28_800);

        $session = $service->login(LoginData::fromArray([
            'email' => 'ADA@example.com',
            'password' => 'correct-password',
            'rememberMe' => true,
        ]), 'test-agent', '127.0.0.1');

        self::assertSame('access-token', $session->accessToken);
        self::assertNotSame($session->refreshToken, $refreshTokens->hash);
        self::assertSame(hash('sha256', $session->refreshToken), $refreshTokens->hash);
    }

    public function testInvalidCredentialsAreRejected(): void
    {
        $user = new User(7, 'Ada Lovelace', 'ada@example.com', password_hash('correct-password', PASSWORD_ARGON2ID));
        $service = new AuthService(new InMemoryUsers($user), new InMemoryRefreshTokens(), new FakeJwt(), 2_592_000, 28_800);

        $this->expectException(AuthenticationException::class);
        $service->login(LoginData::fromArray([
            'email' => 'ada@example.com',
            'password' => 'wrong-password',
        ]), 'test-agent', '127.0.0.1');
    }
}

final class FakeJwt implements JwtServiceContract
{
    public function issue(User $user): string
    {
        return 'access-token';
    }

    public function validate(string $token): array
    {
        return ['sub' => '1'];
    }
}

final class InMemoryUsers implements UserRepositoryContract
{
    public function __construct(private readonly User $user)
    {
    }

    public function findByEmail(string $email): ?User
    {
        return strtolower($email) === strtolower($this->user->email) ? $this->user : null;
    }

    public function findById(int $id): ?User
    {
        return $id === $this->user->id ? $this->user : null;
    }
}

final class InMemoryRefreshTokens implements RefreshTokenRepositoryContract
{
    public ?string $hash = null;
    public function create(int $userId, string $tokenHash, string $userAgent, string $ipAddress, string $expiresAt): void
    {
        $this->hash = $tokenHash;
    }

    public function findValidByHash(string $tokenHash): ?RefreshToken
    {
        return null;
    }

    public function consume(int $id): bool
    {
        return true;
    }

    public function revokeByHash(string $tokenHash): void
    {
    }
}
