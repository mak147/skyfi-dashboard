<?php

declare(strict_types=1);

namespace SkyFi\Integration\DTOs;

final class CreateApiKeyData
{
    /** @param list<string> $scopes @param list<string>|null $ipAllowList */
    public function __construct(
        public readonly ?int $clientApplicationId = null,
        public readonly string $name = '',
        public readonly array $scopes = [],
        public readonly ?array $ipAllowList = null,
        public readonly ?int $rateLimitPerMinute = null,
        public readonly ?string $expiresAt = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            clientApplicationId: isset($data['client_application_id']) ? (int) $data['client_application_id'] : null,
            name: (string) ($data['name'] ?? ''),
            scopes: (array) ($data['scopes'] ?? []),
            ipAllowList: isset($data['ip_allow_list']) ? (array) $data['ip_allow_list'] : null,
            rateLimitPerMinute: isset($data['rate_limit_per_minute']) ? (int) $data['rate_limit_per_minute'] : null,
            expiresAt: $data['expires_at'] ?? null,
        );
    }
}
