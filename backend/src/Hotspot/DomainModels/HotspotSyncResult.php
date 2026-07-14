<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\DomainModels;

final class HotspotSyncResult
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes)
    {
    }

    public function routerId(): int
    {
        return (int) $this->attributes['router_id'];
    }

    public function routerName(): string
    {
        return (string) ($this->attributes['router_name'] ?? '');
    }

    public function status(): string
    {
        return (string) $this->attributes['status'];
    }

    public function totalUsersInDb(): int
    {
        return (int) ($this->attributes['total_users_in_db'] ?? 0);
    }

    public function totalUsersOnRouter(): int
    {
        return (int) ($this->attributes['total_users_on_router'] ?? 0);
    }

    /** @return array<int, array<string, mixed>> */
    public function discrepancies(): array
    {
        return is_array($this->attributes['discrepancies'] ?? null) ? $this->attributes['discrepancies'] : [];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'router_id' => $this->routerId(),
            'router_name' => $this->routerName(),
            'status' => $this->status(),
            'total_users_in_db' => $this->totalUsersInDb(),
            'total_users_on_router' => $this->totalUsersOnRouter(),
            'discrepancies' => $this->discrepancies(),
            'checked_at' => $this->attributes['checked_at'] ?? gmdate('Y-m-d H:i:s'),
        ];
    }
}
