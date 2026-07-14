<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\DTOs;

final class AlertListFilters
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 15,
        public readonly ?string $status = null,
        public readonly ?string $severity = null,
        public readonly ?string $deviceType = null,
        public readonly ?int $deviceId = null,
    ) {
    }

    /** @param array<string, mixed> $params */
    public static function fromRequest(array $params): self
    {
        return new self(
            page: isset($params['page']) && (int) $params['page'] > 0 ? (int) $params['page'] : 1,
            perPage: isset($params['per_page']) && (int) $params['per_page'] > 0 ? (int) $params['per_page'] : 15,
            status: isset($params['status']) && is_string($params['status']) && $params['status'] !== '' ? $params['status'] : null,
            severity: isset($params['severity']) && is_string($params['severity']) && $params['severity'] !== '' ? $params['severity'] : null,
            deviceType: isset($params['device_type']) && is_string($params['device_type']) && $params['device_type'] !== '' ? $params['device_type'] : null,
            deviceId: isset($params['device_id']) && (int) $params['device_id'] > 0 ? (int) $params['device_id'] : null,
        );
    }
}
