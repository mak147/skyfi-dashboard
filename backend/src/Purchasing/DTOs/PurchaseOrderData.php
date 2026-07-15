<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\DTOs;

final class PurchaseOrderData
{
    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function __construct(
        public readonly int $vendorId,
        public readonly ?int $warehouseId,
        public readonly ?int $purchaseRequestId,
        public readonly string $currency,
        public readonly float $taxRate,
        public readonly float $discountAmount,
        public readonly ?string $orderDate,
        public readonly ?string $expectedDeliveryDate,
        public readonly ?string $notes,
        public readonly array $items,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            vendorId: (int) ($data['vendor_id'] ?? 0),
            warehouseId: isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : null,
            purchaseRequestId: isset($data['purchase_request_id']) ? (int) $data['purchase_request_id'] : null,
            currency: (string) ($data['currency'] ?? 'PKR'),
            taxRate: (float) ($data['tax_rate'] ?? 0),
            discountAmount: (float) ($data['discount_amount'] ?? 0),
            orderDate: isset($data['order_date']) ? (string) $data['order_date'] : null,
            expectedDeliveryDate: isset($data['expected_delivery_date']) ? (string) $data['expected_delivery_date'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            items: is_array($data['items'] ?? null) ? $data['items'] : [],
        );
    }
}
