<?php

declare(strict_types=1);

namespace SkyFi\Shared\Http\Middleware;

use SkyFi\Shared\Http\Response;

/**
 * Applies security-related HTTP headers to every response.
 *
 * References:
 *   - CSP: https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP
 *   - HSTS: RFC 6797
 *   - X-Frame-Options: RFC 7034
 *   - X-Content-Type-Options: https://web.dev/articles/x-content-type-options
 */
final class SecurityHeadersMiddleware
{
    /**
     * Wraps a response with standard security headers.
     *
     * @param Response $response The response to augment.
     * @param bool $isProduction Whether to apply strict HSTS (only in production).
     * @param string $cspNonce Base64 nonce for inline scripts (empty = policy without nonce).
     */
    public static function apply(Response $response, bool $isProduction = false, string $cspNonce = ''): Response
    {
        // Content-Security-Policy: restrict script/style sources
        $cspDirectives = [
            "default-src 'self'",
            "script-src 'self'" . ($cspNonce !== '' ? " 'nonce-{$cspNonce}'" : " 'unsafe-inline'"),
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "font-src 'self'",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ];

        $response = $response->withHeaders([
            'Content-Security-Policy' => implode('; ', $cspDirectives),
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ]);

        // HSTS: only enable in production to avoid breaking local development
        if ($isProduction) {
            $response = $response->withHeaders([
                'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
            ]);
        }

        return $response;
    }
}
