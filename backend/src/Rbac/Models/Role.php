<?php

declare(strict_types=1);

namespace SkyFi\Rbac\Models;

final class Role
{
    /**
     * @param array<int, Permission> $permissions
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $description,
        public readonly array $permissions = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'permissions' => array_map(fn(Permission $p) => $p->toArray(), $this->permissions),
        ];
    }
}
