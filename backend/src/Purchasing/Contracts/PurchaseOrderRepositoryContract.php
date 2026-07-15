<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Contracts;

use SkyFi\Purchasing\DomainModels\PurchaseOrder;
use SkyFi\Purchasing\DTOs\PurchaseOrderData;
use SkyFi\Purchasing\DTOs\PurchaseOrderListFilters;

interface PurchaseOrderRepositoryContract
{
    /** @return array{items: array<int, PurchaseOrder>, total: int, page: int, perPage: int, lastPage: int} */
    public function list(PurchaseOrderListFilters $filters): array;
    public function find(int $id): ?PurchaseOrder;
    public function create(PurchaseOrderData $data, int $actorId): PurchaseOrder;
    public function update(int $id, PurchaseOrderData $data, int $actorId): PurchaseOrder;
    public function updateStatus(int $id, string $status, int $actorId): PurchaseOrder;
    public function addApproval(int $orderId, int $approverId, string $decision, ?string $comments): void;
    public function updateItemReceived(int $itemId, float $receivedDelta, float $damagedDelta): void;
    public function nextPoNumber(): string;
    /** @return array<int, array<string, mixed>> */
    public function getApprovals(int $orderId): array;
    /** @return array<int, array<string, mixed>> */
    public function getItems(int $orderId): array;
    public function recalculateTotals(int $orderId): void;
}
