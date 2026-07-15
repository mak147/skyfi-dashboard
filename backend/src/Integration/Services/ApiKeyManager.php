<?php

declare(strict_types=1);

namespace SkyFi\Integration\Services;

/**
 * Manages API key generation, hashing, and authentication.
 */
final class ApiKeyManager
{
    private const PREFIX = 'skyfi_';

    /**
     * Generate a new API key and return both the plain text and hash.
     *
     * @return array{plain_text: string, key_hash: string, key_prefix: string}
     */
    public function generate(): array
    {
        $raw = bin2hex(random_bytes(32));
        $plainText = self::PREFIX . $raw;
        $keyHash = hash('sha256', $plainText);
        $keyPrefix = substr($plainText, 0, 12);

        return [
            'plain_text' => $plainText,
            'key_hash' => $keyHash,
            'key_prefix' => $keyPrefix,
        ];
    }

    /**
     * Hash a plain text API key for lookup.
     */
    public function hash(string $plainKey): string
    {
        return hash('sha256', $plainKey);
    }

    /**
     * Extract the prefix from a plain text key for identification.
     */
    public function prefix(string $plainKey): string
    {
        return substr($plainKey, 0, 12);
    }

    /**
     * Validate the format of a plain text API key.
     */
    public function isValidFormat(string $plainKey): bool
    {
        return str_starts_with($plainKey, self::PREFIX) && strlen($plainKey) > 20;
    }
}
