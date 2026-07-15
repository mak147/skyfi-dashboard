<?php

declare(strict_types=1);

namespace SkyFi\Notifications\DTOs;

final class DeliveryListFilters
{
    public function __construct(
        public readonly ?string $channel = null,
        public readonly ?string $status = null,
        public readonly ?int $recipientUserId = null,
        public readonly ?string $search = null,
        public readonly int $page = 1,
        public readonly int $perPage = 25,
    ) {}

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        $page = (int) ($query['page']['number'] ?? $query['page'] ?? 1);
        $perPage = (int) ($query['page']['size'] ?? $query['per_page'] ?? 25);

        return new self(
            channel: isset($query['channel']) && $query['channel'] !== '' ? (string) $query['channel'] : null,
            status: isset($query['status']) && $query['status'] !== '' ? (string) $query['status'] : null,
            recipientUserId: isset($query['recipient_user_id']) && $query['recipient_user_id'] !== '' ? (int) $query['recipient_user_id'] : null,
            search: isset($query['search']) && $query['search'] !== '' ? (string) $query['search'] : null,
            page: max(1, $page),
            perPage: max(1, min(100, $perPage)),
        );
    }
}
