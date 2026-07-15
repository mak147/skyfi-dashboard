<?php

declare(strict_types=1);

namespace SkyFi\Integration\DTOs;

final class CreateWebhookData
{
    /** @param list<string> $events @param array<string, mixed> $retryPolicy @param array<string, mixed>|null $filterRules */
    public function __construct(
        public readonly ?int $clientApplicationId = null,
        public readonly string $name = '',
        public readonly string $url = '',
        public readonly array $events = [],
        public readonly bool $isActive = true,
        public readonly bool $isInbound = false,
        public readonly array $retryPolicy = ['max_attempts' => 3, 'backoff' => 'exponential'],
        public readonly ?array $filterRules = null,
        public readonly string $contentType = 'application/json',
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            clientApplicationId: isset($data['client_application_id']) ? (int) $data['client_application_id'] : null,
            name: (string) ($data['name'] ?? ''),
            url: (string) ($data['url'] ?? ''),
            events: (array) ($data['events'] ?? []),
            isActive: (bool) ($data['is_active'] ?? true),
            isInbound: (bool) ($data['is_inbound'] ?? false),
            retryPolicy: (array) ($data['retry_policy'] ?? ['max_attempts' => 3, 'backoff' => 'exponential']),
            filterRules: $data['filter_rules'] ?? null,
            contentType: (string) ($data['content_type'] ?? 'application/json'),
        );
    }
}
