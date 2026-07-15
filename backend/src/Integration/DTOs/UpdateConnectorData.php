<?php

declare(strict_types=1);

namespace SkyFi\Integration\DTOs;

final class UpdateConnectorData
{
    /** @param array<string, mixed>|null $config */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?array $config = null,
        public readonly ?bool $isEnabled = null,
        public readonly ?int $rateLimitPerMinute = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            description: array_key_exists('description', $data) ? $data['description'] : null,
            config: $data['config'] ?? null,
            isEnabled: $data['is_enabled'] ?? null,
            rateLimitPerMinute: $data['rate_limit_per_minute'] ?? null,
        );
    }
}
