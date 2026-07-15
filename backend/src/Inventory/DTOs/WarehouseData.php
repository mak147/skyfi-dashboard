<?php

declare(strict_types=1);

namespace SkyFi\Inventory\DTOs;

final class WarehouseData
{
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly string $type,
        public readonly string $status,
        public readonly ?int $managerUserId,
        public readonly ?string $address,
        public readonly ?string $city,
        public readonly ?string $region,
        public readonly ?string $notes,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $text = static fn(string $key): ?string => trim((string) ($data[$key] ?? '')) ?: null;
        return new self(
            strtoupper(trim((string) ($data['code'] ?? ''))),
            trim((string) ($data['name'] ?? '')),
            (string) ($data['type'] ?? 'branch'),
            (string) ($data['status'] ?? 'active'),
            isset($data['manager_user_id']) && $data['manager_user_id'] !== '' ? (int) $data['manager_user_id'] : null,
            $text('address'),
            $text('city'),
            $text('region'),
            $text('notes'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'status' => $this->status,
            'manager_user_id' => $this->managerUserId,
            'address' => $this->address,
            'city' => $this->city,
            'region' => $this->region,
            'notes' => $this->notes,
        ];
    }
}
