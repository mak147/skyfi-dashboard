<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\DTOs;

final class GoodsReceiptData
{
    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function __construct(
        public readonly int $purchaseOrderId,
        public readonly ?int $warehouseId,
        public readonly ?string $notes,
        public readonly array $items,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            purchaseOrderId: (int) ($data['purchase_order_id'] ?? 0),
            warehouseId: isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            items: is_array($data['items'] ?? null) ? $data['items'] : [],
        );
    }
}
