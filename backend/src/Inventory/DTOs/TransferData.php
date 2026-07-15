<?php

declare(strict_types=1);

namespace SkyFi\Inventory\DTOs;

final class TransferData
{
    /** @param array<int, array<string, mixed>> $lines */
    public function __construct(
        public readonly int $sourceWarehouseId,
        public readonly int $destinationWarehouseId,
        public readonly array $lines,
        public readonly ?string $expectedAt,
        public readonly ?string $notes,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $expected = trim((string) ($data['expected_at'] ?? ''));
        $notes = trim((string) ($data['notes'] ?? ''));
        return new self(
            (int) ($data['source_warehouse_id'] ?? 0),
            (int) ($data['destination_warehouse_id'] ?? 0),
            is_array($data['lines'] ?? null) ? array_values(array_filter($data['lines'], 'is_array')) : [],
            $expected === '' ? null : $expected,
            $notes === '' ? null : $notes,
        );
    }
}
