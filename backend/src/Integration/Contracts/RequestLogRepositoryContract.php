<?php

declare(strict_types=1);

namespace SkyFi\Integration\Contracts;

use SkyFi\Integration\DomainModels\ApiRequestLog;
use SkyFi\Integration\DTOs\RequestLogFilters;

interface RequestLogRepositoryContract
{
    /** @return array{items: list<ApiRequestLog>, page: int, perPage: int, total: int, lastPage: int} */
    public function list(RequestLogFilters $filters): array;

    public function create(array $data): ApiRequestLog;

    /** @return array<string, mixed> */
    public function aggregateStats(?string $from = null, ?string $to = null): array;
}
