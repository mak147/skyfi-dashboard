<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\DTOs;

final class EventLogListFilters
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly ?string $eventType = null,
        public readonly ?string $severity = null,
        public readonly ?string $sourceType = null,
        public readonly ?int $sourceId = null,
    ) {
    }

    /** @param array<string, mixed> $params */
    public static function fromRequest(array $params): self
    {
        return new self(
            page: isset($params['page']) && (int) $params['page'] > 0 ? (int) $params['page'] : 1,
            perPage: isset($params['per_page']) && (int) $params['per_page'] > 0 ? (int) $params['per_page'] : 20,
            eventType: isset($params['event_type']) && is_string($params['event_type']) && $params['event_type'] !== '' ? $params['event_type'] : null,
            severity: isset($params['severity']) && is_string($params['severity']) && $params['severity'] !== '' ? $params['severity'] : null,
            sourceType: isset($params['source_type']) && is_string($params['source_type']) && $params['source_type'] !== '' ? $params['source_type'] : null,
            sourceId: isset($params['source_id']) && (int) $params['source_id'] > 0 ? (int) $params['source_id'] : null,
        );
    }
}
