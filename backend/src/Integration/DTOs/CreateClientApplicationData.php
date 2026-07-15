<?php

declare(strict_types=1);

namespace SkyFi\Integration\DTOs;

final class CreateClientApplicationData
{
    /** @param list<string>|null $redirectUris */
    public function __construct(
        public readonly string $name = '',
        public readonly ?string $description = null,
        public readonly ?array $redirectUris = null,
        public readonly int $rateLimitPerMinute = 60,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            description: $data['description'] ?? null,
            redirectUris: isset($data['redirect_uris']) ? (array) $data['redirect_uris'] : null,
            rateLimitPerMinute: (int) ($data['rate_limit_per_minute'] ?? 60),
        );
    }
}
