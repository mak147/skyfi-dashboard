<?php

declare(strict_types=1);

namespace SkyFi\Integration\DTOs;

final class RequestLogFilters
{
    public function __construct(
        public readonly ?int $apiKeyId = null,
        public readonly ?int $clientApplicationId = null,
        public readonly ?string $method = null,
        public readonly ?string $path = null,
        public readonly ?int $statusCode = null,
        public readonly int $page = 1,
        public readonly int $perPage = 25,
    ) {}

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        $page = (int) ($query['page']['number'] ?? $query['page'] ?? 1);
        $perPage = (int) ($query['page']['size'] ?? $query['per_page'] ?? 25);

        return new self(
            apiKeyId: isset($query['api_key_id']) ? (int) $query['api_key_id'] : null,
            clientApplicationId: isset($query['client_application_id']) ? (int) $query['client_application_id'] : null,
            method: isset($query['method']) && $query['method'] !== '' ? strtoupper((string) $query['method']) : null,
            path: isset($query['path']) && $query['path'] !== '' ? (string) $query['path'] : null,
            statusCode: isset($query['status_code']) ? (int) $query['status_code'] : null,
            page: max(1, $page),
            perPage: max(1, min(100, $perPage)),
        );
    }
}
