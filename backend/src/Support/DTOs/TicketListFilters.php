<?php

declare(strict_types=1);
namespace SkyFi\Support\DTOs;
final class TicketListFilters
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly array $filters = [],
        public readonly string $sort = "-created_at",
    ) {}
    /** @param array<string, mixed> $query */ public static function fromQuery(
        array $query,
    ): self {
        return new self(
            max(1, (int) ($query["page"]["number"] ?? 1)),
            min(100, max(1, (int) ($query["page"]["size"] ?? 20))),
            is_array($query["filter"] ?? null) ? $query["filter"] : [],
            (string) ($query["sort"] ?? "-created_at"),
        );
    }
}
