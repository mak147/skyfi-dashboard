<?php

declare(strict_types=1);

namespace SkyFi\Pppoe\Contracts;

use SkyFi\Pppoe\DomainModels\PppoeSessionHistory;

interface PppoeSessionRepositoryContract
{
    /** @return array{items: array<int, PppoeSessionHistory>, total: int, page: int, perPage: int, lastPage: int} */
    public function listHistory(
        int $page = 1,
        int $perPage = 15,
        ?int $accountId = null,
        ?int $routerId = null,
        ?string $username = null
    ): array;

    /** @param array<string, mixed> $data */
    public function logSessionHistory(array $data): PppoeSessionHistory;

    public function recordAuthentication(
        int $routerId,
        ?int $accountId,
        string $username,
        ?string $callerId,
        ?string $macAddress,
        string $status,
        ?string $reason
    ): void;

    /** @return array{total_uptime_seconds: int, total_bytes_in: int, total_bytes_out: int, session_count: int} */
    public function getAccountStatistics(int $accountId): array;
}
