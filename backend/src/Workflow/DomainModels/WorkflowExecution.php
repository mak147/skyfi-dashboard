<?php

declare(strict_types=1);

namespace SkyFi\Workflow\DomainModels;

final class WorkflowExecution
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        foreach (['trigger_payload', 'result_json', 'action_results'] as $jsonField) {
            if (isset($row[$jsonField]) && is_string($row[$jsonField])) {
                $row[$jsonField] = json_decode($row[$jsonField], true);
            }
        }
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['workflow_id'] = (int) ($row['workflow_id'] ?? 0);
        $row['version_id'] = (int) ($row['version_id'] ?? 0);
        $row['attempt_number'] = (int) ($row['attempt_number'] ?? 1);
        $row['max_attempts'] = (int) ($row['max_attempts'] ?? 1);
        $row['duration_ms'] = isset($row['duration_ms']) ? (int) $row['duration_ms'] : null;
        $row['actor_user_id'] = isset($row['actor_user_id']) ? (int) $row['actor_user_id'] : null;

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

    public function workflowId(): int
    {
        return (int) ($this->attributes['workflow_id'] ?? 0);
    }

    public function versionId(): int
    {
        return (int) ($this->attributes['version_id'] ?? 0);
    }

    public function status(): string
    {
        return (string) ($this->attributes['status'] ?? 'pending');
    }

    /** @return array<string, mixed>|null */
    public function triggerPayload(): ?array
    {
        $payload = $this->attributes['trigger_payload'] ?? null;

        return is_array($payload) ? $payload : null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
