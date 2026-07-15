<?php

declare(strict_types=1);

namespace SkyFi\Workflow\DTOs;

final class ExecutionListFilters
{
    public function __construct(
        public readonly ?int $workflowId = null,
        public readonly ?string $status = null,
        public readonly ?string $triggerEventKey = null,
        public readonly ?string $triggerSource = null,
        public readonly ?string $search = null,
        public readonly ?string $from = null,
        public readonly ?string $to = null,
        public readonly int $page = 1,
        public readonly int $perPage = 25,
    ) {}

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        return new self(
            workflowId: isset($query['workflow_id']) && $query['workflow_id'] !== ''
                ? (int) $query['workflow_id']
                : null,
            status: isset($query['status']) && $query['status'] !== '' ? (string) $query['status'] : null,
            triggerEventKey: isset($query['trigger_event_key']) && $query['trigger_event_key'] !== ''
                ? (string) $query['trigger_event_key']
                : null,
            triggerSource: isset($query['trigger_source']) && $query['trigger_source'] !== ''
                ? (string) $query['trigger_source']
                : null,
            search: isset($query['search']) && $query['search'] !== '' ? (string) $query['search'] : null,
            from: isset($query['from']) && $query['from'] !== '' ? (string) $query['from'] : null,
            to: isset($query['to']) && $query['to'] !== '' ? (string) $query['to'] : null,
            page: max(1, (int) ($query['page'] ?? 1)),
            perPage: min(100, max(1, (int) ($query['per_page'] ?? 25))),
        );
    }
}
