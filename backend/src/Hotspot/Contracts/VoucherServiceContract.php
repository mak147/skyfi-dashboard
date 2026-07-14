<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Contracts;

use SkyFi\Hotspot\DomainModels\Voucher;
use SkyFi\Hotspot\DomainModels\VoucherBatch;
use SkyFi\Hotspot\DTOs\GenerateVoucherBatchData;
use SkyFi\Hotspot\DTOs\VoucherListFilters;

interface VoucherServiceContract
{
    /** @return array{items: array<int, Voucher>, total: int, page: int, perPage: int, lastPage: int} */
    public function listVouchers(VoucherListFilters $filters): array;

    /** @return array{items: array<int, VoucherBatch>, total: int, page: int, perPage: int, lastPage: int} */
    public function listBatches(int $page = 1, int $perPage = 15, ?string $status = null): array;

    public function getVoucher(int $id): Voucher;

    public function generateBatch(GenerateVoucherBatchData $data, int $actorId, ?string $ip, ?string $userAgent): VoucherBatch;

    public function revokeVoucher(int $id, int $actorId, ?string $ip, ?string $userAgent): Voucher;

    /** @return array<int, array<string, mixed>> */
    public function printVouchers(int $batchId): array;

    /** @return array<string, int> */
    public function getVoucherStats(): array;
}
