<?php

declare(strict_types=1);

namespace SkyFi\Audit\DTOs;

final class ExportRequestData
{
    public function __construct(
        public readonly string $format = 'csv',
        public readonly ?string $module = null,
        public readonly ?string $action = null,
        public readonly ?string $entityType = null,
        public readonly ?int $entityId = null,
        public readonly ?int $userId = null,
        public readonly ?string $severity = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
        public readonly ?string $search = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $entityId = isset($data['entity_id']) ? (int) $data['entity_id'] : null;
        $userId = isset($data['user_id']) ? (int) $data['user_id'] : null;

        return new self(
            format: (string) ($data['format'] ?? 'csv'),
            module: isset($data['module']) && $data['module'] !== '' ? (string) $data['module'] : null,
            action: isset($data['action']) && $data['action'] !== '' ? (string) $data['action'] : null,
            entityType: isset($data['entity_type']) && $data['entity_type'] !== '' ? (string) $data['entity_type'] : null,
            entityId: $entityId > 0 ? $entityId : null,
            userId: $userId > 0 ? $userId : null,
            severity: isset($data['severity']) && $data['severity'] !== '' ? (string) $data['severity'] : null,
            dateFrom: isset($data['date_from']) && $data['date_from'] !== '' ? (string) $data['date_from'] : null,
            dateTo: isset($data['date_to']) && $data['date_to'] !== '' ? (string) $data['date_to'] : null,
            search: isset($data['search']) && $data['search'] !== '' ? (string) $data['search'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toFilterArray(): array
    {
        $filters = [];
        if ($this->module !== null) { $filters['module'] = $this->module; }
        if ($this->action !== null) { $filters['action'] = $this->action; }
        if ($this->entityType !== null) { $filters['entity_type'] = $this->entityType; }
        if ($this->entityId !== null) { $filters['entity_id'] = $this->entityId; }
        if ($this->userId !== null) { $filters['user_id'] = $this->userId; }
        if ($this->severity !== null) { $filters['severity'] = $this->severity; }
        if ($this->dateFrom !== null) { $filters['date_from'] = $this->dateFrom; }
        if ($this->dateTo !== null) { $filters['date_to'] = $this->dateTo; }
        if ($this->search !== null) { $filters['search'] = $this->search; }
        return $filters;
    }
}
