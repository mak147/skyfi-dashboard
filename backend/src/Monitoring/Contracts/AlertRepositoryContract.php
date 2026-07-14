<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Contracts;

use SkyFi\Monitoring\DomainModels\AlertHistoryItem;
use SkyFi\Monitoring\DomainModels\NetworkAlert;
use SkyFi\Monitoring\DTOs\AlertListFilters;
use SkyFi\Monitoring\DTOs\CreateAlertData;

interface AlertRepositoryContract
{
    public function createAlert(CreateAlertData $data): NetworkAlert;

    public function findAlert(int $id): ?NetworkAlert;

    /** @return array{items: array<int, NetworkAlert>, total: int, page: int, per_page: int} */
    public function listAlerts(AlertListFilters $filters): array;

    public function updateAlertStatus(
        int $alertId,
        string $newStatus,
        ?int $actorId,
        ?string $notes,
    ): NetworkAlert;

    public function recordHistoryItem(
        int $alertId,
        ?string $oldStatus,
        string $newStatus,
        ?int $changedBy,
        ?string $notes,
    ): AlertHistoryItem;

    /** @return array<int, AlertHistoryItem> */
    public function getHistoryForAlert(int $alertId): array;

    /** @return array{new: int, acknowledged: int, critical: int, warning: int} */
    public function getAlertCounts(): array;
}
