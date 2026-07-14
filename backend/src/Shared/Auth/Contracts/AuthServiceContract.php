<?php

declare(strict_types=1);

namespace SkyFi\Shared\Auth\Contracts;

use SkyFi\Shared\Auth\Data\AuthSession;
use SkyFi\Shared\Auth\Data\LoginData;

interface AuthServiceContract
{
    /** Authenticates credentials and creates a token pair. */
    public function login(LoginData $data, string $userAgent, string $ipAddress): AuthSession;

    /** Rotates a refresh token and creates a new token pair. */
    public function refresh(string $rawRefreshToken, string $userAgent, string $ipAddress): AuthSession;

    /** Revokes a refresh token when present. */
    public function logout(?string $rawRefreshToken): void;
}
