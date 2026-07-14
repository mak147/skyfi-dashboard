<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\DomainModels;

/** Represents a managed router without exposing its encrypted credential. */
final class Router
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes)
    {
    }

    public function id(): int
    {
        return (int) $this->attributes['id'];
    }

    public function encryptedPassword(): string
    {
        return (string) $this->attributes['api_password_encrypted'];
    }

    public function isEnabled(): bool
    {
        return (bool) $this->attributes['is_enabled'];
    }

    public function host(): string
    {
        return (string) $this->attributes['host'];
    }

    public function apiPort(): int
    {
        return (int) $this->attributes['api_port'];
    }

    public function apiUsername(): string
    {
        return (string) $this->attributes['api_username'];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $attributes = $this->attributes;
        unset($attributes['api_password_encrypted']);
        $attributes['id'] = $this->id();
        $attributes['router_group_id'] = isset($attributes['router_group_id']) ? (int) $attributes['router_group_id'] : null;
        $attributes['api_port'] = $this->apiPort();
        $attributes['is_enabled'] = $this->isEnabled();
        $attributes['created_by'] = isset($attributes['created_by']) ? (int) $attributes['created_by'] : null;
        $attributes['updated_by'] = isset($attributes['updated_by']) ? (int) $attributes['updated_by'] : null;
        $attributes['has_credentials'] = isset($this->attributes['api_password_encrypted'])
            && $this->attributes['api_password_encrypted'] !== '';
        $attributes['tags'] = $attributes['tags'] ?? [];

        return $attributes;
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        $row['is_enabled'] = (bool) ($row['is_enabled'] ?? false);

        return new self($row);
    }
}
