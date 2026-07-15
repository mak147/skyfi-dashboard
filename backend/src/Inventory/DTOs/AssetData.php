<?php

declare(strict_types=1);

namespace SkyFi\Inventory\DTOs;

final class AssetData
{
    public function __construct(
        public readonly int $productId,
        public readonly ?int $vendorId,
        public readonly ?int $networkDeviceId,
        public readonly string $assetTag,
        public readonly string $serialNumber,
        public readonly ?string $macAddress,
        public readonly ?string $imei,
        public readonly ?string $barcode,
        public readonly ?string $qrCodeValue,
        public readonly ?string $purchaseDate,
        public readonly string $acquisitionCost,
        public readonly ?string $warrantyStartsAt,
        public readonly ?string $warrantyExpiresAt,
        public readonly string $status,
        public readonly ?string $notes,
        public readonly ?AssetAssignmentData $initialAssignment,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $id = static fn(string $key): ?int => isset($data[$key]) && $data[$key] !== '' ? (int) $data[$key] : null;
        $text = static fn(string $key): ?string => trim((string) ($data[$key] ?? '')) ?: null;
        $mac = $text('mac_address');
        $assignment = is_array($data['initial_assignment'] ?? null) ? AssetAssignmentData::fromArray($data['initial_assignment']) : null;
        return new self(
            (int) ($data['product_id'] ?? 0),
            $id('vendor_id'),
            $id('network_device_id'),
            strtoupper(trim((string) ($data['asset_tag'] ?? ''))),
            trim((string) ($data['serial_number'] ?? '')),
            $mac !== null ? strtoupper(str_replace('-', ':', $mac)) : null,
            $text('imei'),
            $text('barcode'),
            $text('qr_code_value'),
            $text('purchase_date'),
            number_format(is_numeric($data['acquisition_cost'] ?? null) ? (float) $data['acquisition_cost'] : 0, 4, '.', ''),
            $text('warranty_starts_at'),
            $text('warranty_expires_at'),
            (string) ($data['status'] ?? 'in_stock'),
            $text('notes'),
            $assignment,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'vendor_id' => $this->vendorId,
            'network_device_id' => $this->networkDeviceId,
            'asset_tag' => $this->assetTag,
            'serial_number' => $this->serialNumber,
            'mac_address' => $this->macAddress,
            'imei' => $this->imei,
            'barcode' => $this->barcode,
            'qr_code_value' => $this->qrCodeValue,
            'purchase_date' => $this->purchaseDate,
            'acquisition_cost' => $this->acquisitionCost,
            'warranty_starts_at' => $this->warrantyStartsAt,
            'warranty_expires_at' => $this->warrantyExpiresAt,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
