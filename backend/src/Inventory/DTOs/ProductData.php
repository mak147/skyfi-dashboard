<?php

declare(strict_types=1);

namespace SkyFi\Inventory\DTOs;

final class ProductData
{
    /** @param array<int, array<string, mixed>> $vendors */
    public function __construct(
        public readonly int $categoryId,
        public readonly ?int $modelId,
        public readonly int $unitId,
        public readonly string $sku,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $barcode,
        public readonly ?string $qrCodeValue,
        public readonly string $trackingMode,
        public readonly string $standardCost,
        public readonly string $minimumStock,
        public readonly string $reorderLevel,
        public readonly string $status,
        public readonly array $vendors,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $nullableId = static fn(string $key): ?int => isset($data[$key]) && $data[$key] !== '' ? (int) $data[$key] : null;
        $text = static fn(string $key): ?string => trim((string) ($data[$key] ?? '')) ?: null;

        return new self(
            (int) ($data['category_id'] ?? 0),
            $nullableId('model_id'),
            (int) ($data['unit_id'] ?? 0),
            strtoupper(trim((string) ($data['sku'] ?? ''))),
            trim((string) ($data['name'] ?? '')),
            $text('description'),
            $text('barcode'),
            $text('qr_code_value'),
            (string) ($data['tracking_mode'] ?? 'quantity'),
            self::decimal($data['standard_cost'] ?? 0),
            self::decimal($data['minimum_stock'] ?? 0),
            self::decimal($data['reorder_level'] ?? 0),
            (string) ($data['status'] ?? 'active'),
            is_array($data['vendors'] ?? null) ? array_values($data['vendors']) : [],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'category_id' => $this->categoryId,
            'model_id' => $this->modelId,
            'unit_id' => $this->unitId,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'barcode' => $this->barcode,
            'qr_code_value' => $this->qrCodeValue,
            'tracking_mode' => $this->trackingMode,
            'standard_cost' => $this->standardCost,
            'minimum_stock' => $this->minimumStock,
            'reorder_level' => $this->reorderLevel,
            'status' => $this->status,
        ];
    }

    private static function decimal(mixed $value): string
    {
        return number_format(is_numeric($value) ? (float) $value : 0, 4, '.', '');
    }
}
