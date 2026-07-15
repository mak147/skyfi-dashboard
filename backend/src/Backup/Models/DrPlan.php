<?php

declare(strict_types=1);

namespace SkyFi\Backup\Models;

final class DrPlan
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly int $rpoMinutes,
        public readonly int $rtoMinutes,
        public readonly string $content,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'rpo_minutes' => $this->rpoMinutes,
            'rto_minutes' => $this->rtoMinutes,
            'content' => $this->content,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) $row['name'],
            description: $row['description'] ?? null,
            rpoMinutes: (int) $row['rpo_minutes'],
            rtoMinutes: (int) $row['rto_minutes'],
            content: (string) $row['content'],
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
        );
    }
}
