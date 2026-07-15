<?php

declare(strict_types=1);

namespace SkyFi\Workflow\DomainModels;

final class Workflow
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['active_version_id'] = isset($row['active_version_id']) ? (int) $row['active_version_id'] : null;
        $row['is_enabled'] = (bool) ($row['is_enabled'] ?? false);
        $row['delay_seconds'] = (int) ($row['delay_seconds'] ?? 0);
        $row['max_retries'] = (int) ($row['max_retries'] ?? 0);
        $row['retry_delay_seconds'] = (int) ($row['retry_delay_seconds'] ?? 60);
        $row['execution_count'] = (int) ($row['execution_count'] ?? 0);
        $row['success_count'] = (int) ($row['success_count'] ?? 0);
        $row['failure_count'] = (int) ($row['failure_count'] ?? 0);
        $row['created_by'] = isset($row['created_by']) ? (int) $row['created_by'] : null;
        $row['updated_by'] = isset($row['updated_by']) ? (int) $row['updated_by'] : null;

        return new self($row);
    }

    public function id(): int
    {
        return (int) ($this->attributes['id'] ?? 0);
    }

    public function uuid(): string
    {
        return (string) ($this->attributes['uuid'] ?? '');
    }

    public function status(): string
    {
        return (string) ($this->attributes['status'] ?? 'draft');
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->attributes['is_enabled'] ?? false);
    }

    public function activeVersionId(): ?int
    {
        $id = $this->attributes['active_version_id'] ?? null;

        return $id !== null ? (int) $id : null;
    }

    public function triggerEventKey(): ?string
    {
        $key = $this->attributes['trigger_event_key'] ?? null;

        return $key !== null && $key !== '' ? (string) $key : null;
    }

    public function scheduleMode(): string
    {
        return (string) ($this->attributes['schedule_mode'] ?? 'immediate');
    }

    public function delaySeconds(): int
    {
        return (int) ($this->attributes['delay_seconds'] ?? 0);
    }

    public function cronExpression(): ?string
    {
        $cron = $this->attributes['cron_expression'] ?? null;

        return $cron !== null && $cron !== '' ? (string) $cron : null;
    }

    public function maxRetries(): int
    {
        return (int) ($this->attributes['max_retries'] ?? 0);
    }

    public function retryDelaySeconds(): int
    {
        return (int) ($this->attributes['retry_delay_seconds'] ?? 60);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
