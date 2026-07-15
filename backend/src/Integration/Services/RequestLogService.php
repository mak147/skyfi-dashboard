<?php

declare(strict_types=1);

namespace SkyFi\Integration\Services;

use SkyFi\Integration\Contracts\RequestLogRepositoryContract;
use SkyFi\Integration\DTOs\RequestLogFilters;

final class RequestLogService
{
    public function __construct(
        private readonly RequestLogRepositoryContract $logs,
    ) {}

    /** @return array{items: list<\SkyFi\Integration\DomainModels\ApiRequestLog>, page: int, perPage: int, total: int, lastPage: int} */
    public function list(RequestLogFilters $filters): array
    {
        return $this->logs->list($filters);
    }

    /** @return array<string, mixed> */
    public function aggregateStats(?string $from = null, ?string $to = null): array
    {
        return $this->logs->aggregateStats($from, $to);
    }
}
