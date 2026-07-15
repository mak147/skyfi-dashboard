<?php

declare(strict_types=1);

namespace SkyFi\Inventory\DTOs;

final class StockOperationData
{
    /** @param array<int, array<string, mixed>> $lines */
    public function __construct(
        public readonly string $type,
        public readonly array $lines,
        public readonly ?string $referenceType,
        public readonly ?string $referenceNumber,
        public readonly ?int $supportTicketId,
        public readonly ?int $vendorId,
        public readonly ?string $reason,
        public readonly ?string $notes,
        public readonly string $occurredAt,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(string $type, array $data): self
    {
        $text = static fn(string $key): ?string => trim((string) ($data[$key] ?? '')) ?: null;
        $id = static fn(string $key): ?int => isset($data[$key]) && $data[$key] !== '' ? (int) $data[$key] : null;
        $lines = is_array($data['lines'] ?? null) ? array_values(array_filter($data['lines'], 'is_array')) : [];
        return new self(
            $type,
            $lines,
            $text('reference_type'),
            $text('reference_number'),
            $id('support_ticket_id'),
            $id('vendor_id'),
            $text('reason'),
            $text('notes'),
            $text('occurred_at') ?? gmdate('Y-m-d H:i:s'),
        );
    }
}
