<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Contracts;

use SkyFi\Purchasing\DomainModels\GoodsReceipt;
use SkyFi\Purchasing\DTOs\GoodsReceiptData;
use SkyFi\Purchasing\DTOs\GoodsReceiptListFilters;

interface GoodsReceiptRepositoryContract
{
    /** @return array{items: array<int, GoodsReceipt>, total: int, page: int, perPage: int, lastPage: int} */
    public function list(GoodsReceiptListFilters $filters): array;
    public function find(int $id): ?GoodsReceipt;
    public function create(GoodsReceiptData $data, int $actorId): GoodsReceipt;
    public function nextReceiptNumber(): string;
    /** @return array<int, array<string, mixed>> */
    public function getItems(int $receiptId): array;
    public function markReturned(int $receiptId): GoodsReceipt;
}
