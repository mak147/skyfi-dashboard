<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Services;

use SkyFi\Monitoring\Contracts\EventLoggingRepositoryContract;
use SkyFi\Monitoring\Contracts\EventLoggingServiceContract;
use SkyFi\Monitoring\Contracts\SyncEventRepositoryContract;
use SkyFi\Monitoring\DomainModels\MonitoringEvent;
use SkyFi\Monitoring\DomainModels\SyncEventLog;
use SkyFi\Monitoring\DTOs\EventLogListFilters;
use SkyFi\Monitoring\DTOs\LogMonitoringEventData;
use SkyFi\Monitoring\DTOs\SyncEventLogData;
use SkyFi\Monitoring\Validators\MonitoringValidator;

final class EventLoggingService implements EventLoggingServiceContract
{
    public function __construct(
        private readonly EventLoggingRepositoryContract $eventRepo,
        private readonly SyncEventRepositoryContract $syncRepo,
        private readonly MonitoringValidator $validator,
    ) {
    }

    public function logMonitoringEvent(LogMonitoringEventData $data): MonitoringEvent
    {
        $this->validator->validateEventLog($data);
        return $this->eventRepo->logEvent($data);
    }

    /** @return array{items: array<int, MonitoringEvent>, total: int, page: int, per_page: int} */
    public function listMonitoringEvents(EventLogListFilters $filters): array
    {
        return $this->eventRepo->listEvents($filters);
    }

    public function recordSyncEvent(SyncEventLogData $data): SyncEventLog
    {
        return $this->syncRepo->recordEvent($data);
    }

    /** @return array{items: array<int, SyncEventLog>, total: int, page: int, per_page: int} */
    public function listSyncEvents(int $page = 1, int $perPage = 25, ?int $routerId = null, ?string $syncType = null): array
    {
        return $this->syncRepo->listEvents($page, $perPage, $routerId, $syncType);
    }
}
