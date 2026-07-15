<?php

declare(strict_types=1);

namespace SkyFi\Integration\DTOs;

final class UpdateClientApplicationData
{
    /** @param list<string>|null $redirectUris */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?array $redirectUris = null,
        public readonly ?bool $isActive = null,
        public readonly ?int $rateLimitPerMinute = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            description: array_key_exists('description', $data) ? $data['description'] : null,
            redirectUris: array_key_exists('redirect_uris', $data) ? (array) ($data['redirect_uris'] ?? []) : null,
            isActive: $data['is_active'] ?? null,
            rateLimitPerMinute: $data['rate_limit_per_minute'] ?? null,
        );
    }
}
