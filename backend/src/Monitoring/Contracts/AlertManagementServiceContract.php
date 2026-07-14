<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Contracts;

use SkyFi\Monitoring\DomainModels\NetworkAlert;
use SkyFi\Monitoring\DTOs\AlertListFilters;
use SkyFi\Monitoring\DTOs\CreateAlertData;
use SkyFi\Monitoring\DTOs\UpdateAlertStatusData;

interface AlertManagementServiceContract
{
    /** @return array{items: array<int, NetworkAlert>, total: int, page: int, per_page: int} */
    public function listAlerts(AlertListFilters $filters): array;

    public function getAlert(int $id): array;

    public function createAlert(CreateAlertData $data): NetworkAlert;

    public function acknowledgeAlert(int $id, ?int $actorId = null, ?string $notes = null, ?string $ip = null, ?string $userAgent = null): NetworkAlert;

    public function resolveAlert(int $id, ?int $actorId = null, ?string $notes = null, ?string $ip = null, ?string $userAgent = null): NetworkAlert;

    public function dismissAlert(int $id, ?int $actorId = null, ?string $notes = null, ?string $ip = null, ?string $userAgent = null): NetworkAlert;

    public function updateAlertStatus(int $id, UpdateAlertStatusData $data, ?int $actorId = null, ?string $ip = null, ?string $userAgent = null): NetworkAlert;
}
