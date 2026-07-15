<?php

declare(strict_types=1);

namespace SkyFi\Integration\DTOs;

final class UpdateApiKeyData
{
    /** @param list<string>|null $scopes @param list<string>|null $ipAllowList */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?array $scopes = null,
        public readonly ?array $ipAllowList = null,
        public readonly ?bool $isActive = null,
        public readonly ?int $rateLimitPerMinute = null,
        public readonly ?string $expiresAt = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            scopes: $data['scopes'] ?? null,
            ipAllowList: array_key_exists('ip_allow_list', $data) ? (array) $data['ip_allow_list'] : null,
            isActive: $data['is_active'] ?? null,
            rateLimitPerMinute: $data['rate_limit_per_minute'] ?? null,
            expiresAt: array_key_exists('expires_at', $data) ? (string) ($data['expires_at'] ?? '') : null,
        );
    }
}
