<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\DTOs;

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
            page: isset($params['page']) && (int) $params['page'] > 0 ? (int) $params['page'] : 1,
            perPage: isset($params['per_page']) && (int) $params['per_page'] > 0 ? (int) $params['per_page'] : 25,
            routerId: isset($params['router_id']) && (int) $params['router_id'] > 0 ? (int) $params['router_id'] : null,
            linkStatus: isset($params['link_status']) && is_string($params['link_status']) && $params['link_status'] !== '' ? $params['link_status'] : null,
        );
    }
}
