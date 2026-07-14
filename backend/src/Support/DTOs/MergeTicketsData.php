<?php

declare(strict_types=1);
namespace SkyFi\Support\DTOs;
final class MergeTicketsData
{
    /** @param array<int,int> $sourceIds */ public function __construct(
        public readonly array $sourceIds,
        public readonly string $reason,
    ) {}
    /** @param array<string,mixed> $d */ public static function fromArray(
        array $d,
    ): self {
        return new self(
            array_values(
                array_unique(
                    array_map(
                        "intval",
                        is_array($d["source_ticket_ids"] ?? null)
                            ? $d["source_ticket_ids"]
                            : [],
                    ),
                ),
            ),
            trim((string) ($d["reason"] ?? "")),
        );
    }
}
