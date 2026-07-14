<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Contracts;

use SkyFi\Hotspot\DomainModels\Voucher;
use SkyFi\Hotspot\DTOs\VoucherListFilters;

interface VoucherRepositoryContract
{
    /** @return array{items: array<int, Voucher>, total: int, page: int, perPage: int, lastPage: int} */
    public function list(VoucherListFilters $filters): array;

    public function find(int $id): ?Voucher;

    public function findByCode(string $code): ?Voucher;

    /** @param array<string, mixed> $data */
    public function insert(array $data): Voucher;

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): Voucher;

    public function delete(int $id): void;

    public function countByStatus(string $status): int;

    public function countExpired(): int;

    public function countDailyLogins(?string $date = null): int;
}
