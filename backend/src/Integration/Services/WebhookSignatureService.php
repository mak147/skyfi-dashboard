<?php

declare(strict_types=1);

namespace SkyFi\Integration\Services;

/**
 * Generates and verifies HMAC-SHA256 signatures for webhook payloads.
 */
final class WebhookSignatureService
{
    /**
     * Generate an HMAC-SHA256 signature for a payload.
     *
     * @param array<string, mixed> $payload
     */
    public function sign(array $payload, string $secret): string
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        return hash_hmac('sha256', $body, $secret);
    }

    /**
     * Verify an incoming signature against a payload.
     *
     * @param array<string, mixed> $payload
     */
    public function verify(array $payload, string $secret, string $signature): bool
    {
        $expected = $this->sign($payload, $secret);

        return hash_equals($expected, $signature);
    }
}
