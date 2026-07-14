<?php

declare(strict_types=1);

namespace SkyFi\Shared\Auth\Controllers;

use SkyFi\Shared\Auth\Contracts\AuthServiceContract;
use SkyFi\Shared\Auth\Data\LoginData;
use SkyFi\Shared\Exceptions\AuthenticationException;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class AuthController
{
    public function __construct(
        private readonly AuthServiceContract $auth,
        private readonly string $refreshCookieName,
        private readonly string $refreshCookiePath,
        private readonly bool $refreshCookieSecure,
    ) {
    }

    /** Handles POST /api/v1/auth/login. */
    public function login(Request $request): Response
    {
        $data = LoginData::fromArray($request->body());
        $session = $this->auth->login($data, $request->userAgent(), $request->ipAddress());

        return $this->sessionResponse($session->attributes(), $session->refreshToken, $session->refreshExpiresAt);
    }

    /** Handles POST /api/v1/auth/refresh. */
    public function refresh(Request $request): Response
    {
        $rawToken = $request->refreshToken($this->refreshCookieName);
        if ($rawToken === null) {
            throw new AuthenticationException('A refresh token is required.', 'refresh_token_missing');
        }

        $session = $this->auth->refresh($rawToken, $request->userAgent(), $request->ipAddress());

        return $this->sessionResponse($session->attributes(), $session->refreshToken, $session->refreshExpiresAt);
    }

    /** Handles POST /api/v1/auth/logout. */
    public function logout(Request $request): Response
    {
        $this->auth->logout($request->refreshToken($this->refreshCookieName));

        return ApiResponse::noContent()->withClearedCookie(
            $this->refreshCookieName,
            $this->refreshCookiePath,
            $this->refreshCookieSecure,
        );
    }

    /** @param array<string, mixed> $attributes */
    private function sessionResponse(array $attributes, string $refreshToken, int $refreshExpiresAt): Response
    {
        return ApiResponse::resource('auth-sessions', 'current', $attributes)
            ->withRefreshCookie(
                $this->refreshCookieName,
                $refreshToken,
                $refreshExpiresAt,
                $this->refreshCookiePath,
                $this->refreshCookieSecure,
            );
    }
}
