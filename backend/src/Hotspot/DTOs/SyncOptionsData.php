<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\DTOs;

final class SyncOptionsData
{
    public function __construct(
        public readonly ?int $routerId = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            routerId: isset($data['router_id']) && is_numeric($data['router_id']) && (int) $data['router_id'] > 0 ? (int) $data['router_id'] : null,
        );
    }
}
