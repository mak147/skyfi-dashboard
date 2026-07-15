<?php

declare(strict_types=1);

namespace SkyFi\Shared\Http\Middleware;

use SkyFi\Shared\Http\Request;

/**
 * Shared helper that wraps a route handler with JWT authentication.
 *
 * Every route file currently duplicates this closure. By centralising it
 * here we eliminate boilerplate and ensure consistent claim injection.
 */
final class ProtectRoute
{
    /**
     * Returns a callable that authenticates the request via JWT and injects
     * the decoded claims into the request attributes before invoking $handler.
     *
     * @param callable(Request): \SkyFi\Shared\Http\Response $handler
     * @return callable(Request): \SkyFi\Shared\Http\Response
     */
    public static function wrap(
        JwtAuthMiddleware $authMiddleware,
        callable $handler,
    ): callable {
        return static function (Request $request) use ($handler, $authMiddleware): \SkyFi\Shared\Http\Response {
            $claims = $authMiddleware->authenticate($request);
            $attributes = $request->attributes();
            $attributes['claims'] = $claims;
            return $handler($request->withAttributes($attributes));
        };
    }
}
