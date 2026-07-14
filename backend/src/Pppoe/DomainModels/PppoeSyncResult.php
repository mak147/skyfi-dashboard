<?php

declare(strict_types=1);

namespace SkyFi\Pppoe\DomainModels;

final class PppoeSyncResult
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

    public function totalAccountsInDb(): int
    {
        return (int) ($this->attributes['total_accounts_in_db'] ?? 0);
    }

    public function totalSecretsOnRouter(): int
    {
        return (int) ($this->attributes['total_secrets_on_router'] ?? 0);
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
            'total_accounts_in_db' => $this->totalAccountsInDb(),
            'total_secrets_on_router' => $this->totalSecretsOnRouter(),
            'discrepancies' => $this->discrepancies(),
            'checked_at' => $this->attributes['checked_at'] ?? gmdate('Y-m-d H:i:s'),
        ];
    }
}
