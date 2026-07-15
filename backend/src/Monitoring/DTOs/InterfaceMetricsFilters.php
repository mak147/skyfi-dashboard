<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\DTOs;

use SkyFi\Shared\Http\PaginationInput;

final class InterfaceMetricsFilters
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 25,
        public readonly ?int $routerId = null,
        public readonly ?string $linkStatus = null,
    ) {
    }

    /** @param array<string, mixed> $params */
    public static function fromRequest(array $params): self
    {
        return new self(
            page: PaginationInput::page($params),
            perPage: PaginationInput::perPage($params, 25),
            routerId: isset($params['router_id']) && (int) $params['router_id'] > 0 ? (int) $params['router_id'] : null,
            linkStatus: isset($params['link_status']) && is_string($params['link_status']) && $params['link_status'] !== '' ? $params['link_status'] : null,
        );
    }
}
