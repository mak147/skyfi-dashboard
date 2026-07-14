<?php

declare(strict_types=1);

namespace SkyFi\Shared\Http\Middleware;

use SkyFi\Shared\Auth\Contracts\JwtServiceContract;
use SkyFi\Shared\Exceptions\AuthenticationException;
use SkyFi\Shared\Http\Request;

final class JwtAuthMiddleware
{
    public function __construct(private readonly JwtServiceContract $jwt)
    {
    }

    /**
     * Validates a Bearer access token and returns its claims.
     *
     * @return array<string, mixed> Validated JWT claims.
     * @throws AuthenticationException When the token is missing or invalid.
     */
    public function authenticate(Request $request): array
    {
        $token = $request->bearerToken();
        if ($token === null) {
            throw new AuthenticationException('Authentication is required.', 'token_missing');
        }

        return $this->jwt->validate($token);
    }
}
