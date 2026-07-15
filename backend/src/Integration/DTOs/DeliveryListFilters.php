<?php

declare(strict_types=1);

namespace SkyFi\Integration\DTOs;

final class DeliveryListFilters
{
    public function __construct(
        public readonly ?int $webhookId = null,
        public readonly ?string $eventKey = null,
        public readonly ?string $status = null,
        public readonly int $page = 1,
        public readonly int $perPage = 25,
    ) {}

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        $page = (int) ($query['page']['number'] ?? $query['page'] ?? 1);
        $perPage = (int) ($query['page']['size'] ?? $query['per_page'] ?? 25);

        return new self(
            webhookId: isset($query['webhook_id']) ? (int) $query['webhook_id'] : null,
            eventKey: isset($query['event_key']) && $query['event_key'] !== '' ? (string) $query['event_key'] : null,
            status: isset($query['status']) && $query['status'] !== '' ? (string) $query['status'] : null,
            page: max(1, $page),
            perPage: max(1, min(100, $perPage)),
        );
    }
}
