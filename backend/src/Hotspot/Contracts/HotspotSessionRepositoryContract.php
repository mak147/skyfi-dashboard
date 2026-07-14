<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Contracts;

use SkyFi\Hotspot\DomainModels\HotspotSessionHistory;

interface HotspotSessionRepositoryContract
{
    /** @return array{items: array<int, HotspotSessionHistory>, total: int, page: int, perPage: int, lastPage: int} */
    public function listHistory(int $page = 1, int $perPage = 15, ?int $userId = null, ?int $routerId = null, ?string $username = null): array;

    /** @param array<string, mixed> $data */
    public function logSessionHistory(array $data): HotspotSessionHistory;

    public function recordLogin(
        int $routerId,
        ?int $hotspotUserId,
        string $username,
        ?string $macAddress,
        ?string $ipAddress,
        string $status,
        ?string $reason
    ): void;

    /** @return array{items: array<int, array<string, mixed>>, total: int, page: int, perPage: int, lastPage: int} */
    public function listLoginHistory(int $page = 1, int $perPage = 15, ?int $userId = null, ?int $routerId = null): array;

    /** @return array<string, int> */
    public function getUserStatistics(int $userId): array;
}
