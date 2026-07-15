<?php

declare(strict_types=1);

namespace SkyFi\Workflow\DomainModels;

final class WorkflowVersion
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        if (isset($row['definition']) && is_string($row['definition'])) {
            $row['definition'] = json_decode($row['definition'], true) ?: [];
        }
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['workflow_id'] = (int) ($row['workflow_id'] ?? 0);
        $row['version_number'] = (int) ($row['version_number'] ?? 0);
        $row['is_published'] = (bool) ($row['is_published'] ?? true);
        $row['created_by'] = isset($row['created_by']) ? (int) $row['created_by'] : null;

        return new self($row);
    }

    public function id(): int
    {
        return (int) ($this->attributes['id'] ?? 0);
    }

    public function workflowId(): int
    {
        return (int) ($this->attributes['workflow_id'] ?? 0);
    }

    public function versionNumber(): int
    {
        return (int) ($this->attributes['version_number'] ?? 0);
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $def = $this->attributes['definition'] ?? [];

        return is_array($def) ? $def : [];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
