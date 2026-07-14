<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Contracts;

use SkyFi\Monitoring\DomainModels\MonitoringEvent;
use SkyFi\Monitoring\DomainModels\SyncEventLog;
use SkyFi\Monitoring\DTOs\EventLogListFilters;
use SkyFi\Monitoring\DTOs\LogMonitoringEventData;
use SkyFi\Monitoring\DTOs\SyncEventLogData;

interface EventLoggingServiceContract
{
    public function logMonitoringEvent(LogMonitoringEventData $data): MonitoringEvent;

    /** @return array{items: array<int, MonitoringEvent>, total: int, page: int, per_page: int} */
    public function listMonitoringEvents(EventLogListFilters $filters): array;

    public function recordSyncEvent(SyncEventLogData $data): SyncEventLog;

    /** @return array{items: array<int, SyncEventLog>, total: int, page: int, per_page: int} */
    public function listSyncEvents(int $page = 1, int $perPage = 25, ?int $routerId = null, ?string $syncType = null): array;
}
