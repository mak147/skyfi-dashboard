<?php

declare(strict_types=1);

namespace SkyFi\Rbac\Models;

final class Permission
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $description,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
