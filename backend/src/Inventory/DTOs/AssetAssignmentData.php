<?php

declare(strict_types=1);

namespace SkyFi\Inventory\DTOs;

final class AssetAssignmentData
{
    public function __construct(
        public readonly string $assignmentType,
        public readonly ?int $warehouseLocationId,
        public readonly ?int $customerId,
        public readonly ?int $towerId,
        public readonly ?int $popSiteId,
        public readonly ?int $technicianId,
        public readonly ?string $notes,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $id = static fn(string $key): ?int => isset($data[$key]) && $data[$key] !== '' ? (int) $data[$key] : null;
        $notes = trim((string) ($data['notes'] ?? ''));
        return new self(
            (string) ($data['assignment_type'] ?? ''),
            $id('warehouse_location_id'),
            $id('customer_id'),
            $id('tower_id'),
            $id('pop_site_id'),
            $id('technician_id'),
            $notes === '' ? null : $notes,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'assignment_type' => $this->assignmentType,
            'warehouse_location_id' => $this->warehouseLocationId,
            'customer_id' => $this->customerId,
            'tower_id' => $this->towerId,
            'pop_site_id' => $this->popSiteId,
            'technician_id' => $this->technicianId,
            'notes' => $this->notes,
        ];
    }
}
