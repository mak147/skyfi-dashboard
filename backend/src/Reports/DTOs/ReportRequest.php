<?php

declare(strict_types=1);

namespace SkyFi\Reports\DTOs;

final class ReportRequest
{
    /** @param array<string, mixed> $filters @param array<int, string> $columns */
    public function __construct(
        public readonly string $reportKey,
        public readonly array $filters,
        public readonly int $page = 1,
        public readonly int $perPage = 25,
        public readonly ?string $sortBy = null,
        public readonly string $sortDirection = 'desc',
        public readonly array $columns = [],
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            trim((string)($data['report_key'] ?? '')),
            is_array($data['filters'] ?? null) ? $data['filters'] : [],
            max(1, (int)($data['page'] ?? 1)),
            min(100, max(1, (int)($data['per_page'] ?? 25))),
            isset($data['sort_by']) ? (string)$data['sort_by'] : null,
            strtolower((string)($data['sort_direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc',
            is_array($data['columns'] ?? null) ? array_values(array_filter($data['columns'], 'is_string')) : [],
        );
    }
}
