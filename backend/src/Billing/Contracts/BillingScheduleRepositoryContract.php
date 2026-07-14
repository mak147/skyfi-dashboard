<?php

declare(strict_types=1);

namespace SkyFi\Billing\Contracts;

use SkyFi\Billing\Models\BillingSchedule;

interface BillingScheduleRepositoryContract
{
    public function find(int $id): ?BillingSchedule;

    public function findByConnection(int $connectionId): ?BillingSchedule;

    /**
     * @return array<BillingSchedule>
     */
    public function findDue(string $date, ?array $connectionIds = null): array;

    /**
     * @return array{items: array<BillingSchedule>, total: int, page: int, perPage: int, lastPage: int}
     */
    public function list(int $page, int $perPage, string $sort): array;

    public function create(array $data): BillingSchedule;

    public function update(int $id, array $data): BillingSchedule;

    public function updateNextBillDate(int $connectionId, string $nextBillDate): void;
}
