<?php

declare(strict_types=1);

namespace SkyFi\Workflow\DTOs;

final class WorkflowListFilters
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $status = null,
        public readonly ?string $triggerEventKey = null,
        public readonly ?bool $isEnabled = null,
        public readonly int $page = 1,
        public readonly int $perPage = 25,
    ) {}

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        $enabled = $query['is_enabled'] ?? null;
        $isEnabled = null;
        if ($enabled !== null && $enabled !== '') {
            $isEnabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return new self(
            search: isset($query['search']) && $query['search'] !== '' ? (string) $query['search'] : null,
            status: isset($query['status']) && $query['status'] !== '' ? (string) $query['status'] : null,
            triggerEventKey: isset($query['trigger_event_key']) && $query['trigger_event_key'] !== ''
                ? (string) $query['trigger_event_key']
                : null,
            isEnabled: $isEnabled,
            page: max(1, (int) ($query['page'] ?? 1)),
            perPage: min(100, max(1, (int) ($query['per_page'] ?? 25))),
        );
    }
}
