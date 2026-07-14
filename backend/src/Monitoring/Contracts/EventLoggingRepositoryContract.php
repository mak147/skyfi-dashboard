<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Contracts;

use SkyFi\Monitoring\DomainModels\MonitoringEvent;
use SkyFi\Monitoring\DTOs\EventLogListFilters;
use SkyFi\Monitoring\DTOs\LogMonitoringEventData;

interface EventLoggingRepositoryContract
{
    public function logEvent(LogMonitoringEventData $data): MonitoringEvent;

    /** @return array{items: array<int, MonitoringEvent>, total: int, page: int, per_page: int} */
    public function listEvents(EventLogListFilters $filters): array;
}
