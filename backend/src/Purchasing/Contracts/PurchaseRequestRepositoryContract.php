<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Contracts;

use SkyFi\Purchasing\DomainModels\PurchaseRequest;
use SkyFi\Purchasing\DTOs\PurchaseRequestData;
use SkyFi\Purchasing\DTOs\PurchaseRequestListFilters;

interface PurchaseRequestRepositoryContract
{
    /** @return array{items: array<int, PurchaseRequest>, total: int, page: int, perPage: int, lastPage: int} */
    public function list(PurchaseRequestListFilters $filters): array;
    public function find(int $id): ?PurchaseRequest;
    public function create(PurchaseRequestData $data, int $actorId): PurchaseRequest;
    public function update(int $id, PurchaseRequestData $data, int $actorId): PurchaseRequest;
    public function updateStatus(int $id, string $status, int $actorId): PurchaseRequest;
    public function addApproval(int $requestId, int $approverId, string $decision, ?string $comments): void;
    public function nextRequestNumber(): string;
    /** @return array<int, array<string, mixed>> */
    public function getApprovals(int $requestId): array;
    /** @return array<int, array<string, mixed>> */
    public function getItems(int $requestId): array;
}
