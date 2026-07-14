<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Contracts;

use SkyFi\Hotspot\DomainModels\VoucherBatch;

interface VoucherBatchRepositoryContract
{
    /** @return array{items: array<int, VoucherBatch>, total: int, page: int, perPage: int, lastPage: int} */
    public function list(int $page = 1, int $perPage = 15, ?string $status = null): array;

    public function find(int $id): ?VoucherBatch;

    public function existsByBatchCode(string $batchCode): bool;

    /** @param array<string, mixed> $data */
    public function insert(array $data): VoucherBatch;

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): VoucherBatch;

    public function delete(int $id): void;
}
