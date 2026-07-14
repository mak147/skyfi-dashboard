<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Contracts;

use SkyFi\Monitoring\DomainModels\InterfaceSnapshot;
use SkyFi\Monitoring\DTOs\InterfaceMetricsFilters;

interface InterfaceSnapshotRepositoryContract
{
    /** @param array<string, mixed> $data */
    public function recordSnapshot(array $data): InterfaceSnapshot;

    public function getLatestSnapshotForInterface(int $routerId, string $interfaceName): ?InterfaceSnapshot;

    /** @return array{items: array<int, InterfaceSnapshot>, total: int, page: int, per_page: int} */
    public function listSnapshots(InterfaceMetricsFilters $filters): array;

    /** @return array<int, InterfaceSnapshot> */
    public function getLatestSnapshotsForRouter(int $routerId): array;
}
