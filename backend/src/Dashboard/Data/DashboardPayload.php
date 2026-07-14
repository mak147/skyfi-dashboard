<?php

declare(strict_types=1);

namespace SkyFi\Dashboard\Data;

final class DashboardPayload
{
    /**
     * @param array<int, string> $roles
     * @param array<int, array<string, mixed>> $widgets
     */
    public function __construct(
        private readonly string $id,
        private readonly string $title,
        private readonly string $description,
        private readonly array $roles,
        private readonly array $widgets,
        private readonly int $cacheTtlSeconds = 300,
        private readonly ?string $generatedAt = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'generatedAt' => $this->generatedAt ?? gmdate(DATE_ATOM),
            'cacheTtlSeconds' => $this->cacheTtlSeconds,
            'scope' => [
                'key' => $this->id,
                'title' => $this->title,
                'description' => $this->description,
            ],
            'roles' => $this->roles,
            'widgets' => $this->widgets,
        ];
    }
}
