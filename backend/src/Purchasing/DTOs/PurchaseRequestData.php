<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\DTOs;

final class PurchaseRequestData
{
    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function __construct(
        public readonly int $requesterUserId,
        public readonly ?string $department,
        public readonly string $priority,
        public readonly ?string $requiredDate,
        public readonly ?string $notes,
        public readonly array $items,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, int $actorId): self
    {
        return new self(
            requesterUserId: (int) ($data['requester_user_id'] ?? $actorId),
            department: isset($data['department']) ? (string) $data['department'] : null,
            priority: (string) ($data['priority'] ?? 'normal'),
            requiredDate: isset($data['required_date']) ? (string) $data['required_date'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            items: is_array($data['items'] ?? null) ? $data['items'] : [],
        );
    }
}
