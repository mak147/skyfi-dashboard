<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Contracts;

use SkyFi\Monitoring\DomainModels\SyncEventLog;
use SkyFi\Monitoring\DTOs\SyncEventLogData;

interface SyncEventRepositoryContract
{
    public function recordEvent(SyncEventLogData $data): SyncEventLog;

    /** @return array{items: array<int, SyncEventLog>, total: int, page: int, per_page: int} */
    public function listEvents(int $page = 1, int $perPage = 25, ?int $routerId = null, ?string $syncType = null): array;
}
