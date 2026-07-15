<?php

declare(strict_types=1);

namespace SkyFi\Integration\DTOs;

final class UpdateWebhookData
{
    /** @param list<string>|null $events @param array<string, mixed>|null $retryPolicy @param array<string, mixed>|null $filterRules */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $url = null,
        public readonly ?array $events = null,
        public readonly ?bool $isActive = null,
        public readonly ?array $retryPolicy = null,
        public readonly ?array $filterRules = null,
        public readonly ?string $contentType = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            url: $data['url'] ?? null,
            events: $data['events'] ?? null,
            isActive: $data['is_active'] ?? null,
            retryPolicy: $data['retry_policy'] ?? null,
            filterRules: array_key_exists('filter_rules', $data) ? $data['filter_rules'] : null,
            contentType: $data['content_type'] ?? null,
        );
    }
}
