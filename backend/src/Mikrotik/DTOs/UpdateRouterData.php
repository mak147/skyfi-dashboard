<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\DTOs;

final class UpdateRouterData
{
    /** @param array<int, int> $tagIds */
    public function __construct(
        public readonly string $name,
        public readonly string $host,
        public readonly int $apiPort,
        public readonly string $apiUsername,
        public readonly ?string $apiPassword,
        public readonly ?int $routerGroupId,
        public readonly array $tagIds,
        public readonly ?string $location,
        public readonly ?string $site,
        public readonly ?string $notes,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $create = CreateRouterData::fromArray([
            ...$data,
            'api_password' => isset($data['api_password']) ? $data['api_password'] : '',
        ]);

        return new self(
            name: $create->name,
            host: $create->host,
            apiPort: $create->apiPort,
            apiUsername: $create->apiUsername,
            apiPassword: isset($data['api_password']) && trim((string) $data['api_password']) !== ''
                ? (string) $data['api_password']
                : null,
            routerGroupId: $create->routerGroupId,
            tagIds: $create->tagIds,
            location: $create->location,
            site: $create->site,
            notes: $create->notes,
        );
    }
}
