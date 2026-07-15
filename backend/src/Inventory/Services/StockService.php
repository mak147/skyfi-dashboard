<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Services;

use SkyFi\Inventory\Contracts\StockRepositoryContract;
use SkyFi\Inventory\DomainModels\StockMovement;
use SkyFi\Inventory\DTOs\StockMovementListFilters;
use SkyFi\Inventory\DTOs\StockOperationData;
use SkyFi\Inventory\Validators\StockValidator;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Events\EventDispatcher;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class StockService
{
    public function __construct(
        private readonly StockRepositoryContract $repository,
        private readonly StockValidator $validator,
        private readonly AuditLoggerContract $audit,
        private readonly InventoryFinanceIntegrationService $finance,
    ) {
    }

    public function list(StockMovementListFilters $filters): array
    {
        return $this->repository->list($filters);
    }

    public function get(int $id): StockMovement
    {
        return $this->repository->find($id) ?? throw new NotFoundException('Stock movement not found.');
    }

    public function post(StockOperationData $data, int $actorId, ?string $ip = null, ?string $agent = null): StockMovement
    {
        $this->validator->validate($data);
        try {
            $movement = $this->repository->post($data, $actorId);
        } catch (\PDOException $exception) {
            throw new ValidationException([['code' => 'invalid_stock_reference', 'detail' => 'A product, asset, location, vendor, or support-ticket reference is invalid or duplicated.']]);
        }
        $this->audit->log($actorId, 'inventory.stock.' . $data->type, 'inventory_stock_movement', $movement->id(), null, $movement->toArray(), $ip, $agent);
        EventDispatcher::dispatch('inventory.stock.posted', $movement->toArray());
        $this->finance->tryPostMovement($movement->id(), $actorId);
        return $this->get($movement->id());
    }

    public function reverse(int $id, string $reason, int $actorId, ?string $ip = null, ?string $agent = null): StockMovement
    {
        $old = $this->get($id);
        $movement = $this->repository->reverse($id, $reason, $actorId);
        $this->audit->log($actorId, 'inventory.stock.reversed', 'inventory_stock_movement', $id, $old->toArray(), ['reversal' => $movement->toArray()], $ip, $agent);
        EventDispatcher::dispatch('inventory.stock.reversed', $movement->toArray());
        $this->finance->tryPostMovement($movement->id(), $actorId);
        return $this->get($movement->id());
    }

    public function balances(array $filters): array
    {
        return $this->repository->balances($filters);
    }

    public function dashboard(): array
    {
        return $this->repository->dashboard();
    }

    public function accountingSettings(): array
    {
        return $this->repository->accountingSettings();
    }

    public function updateAccountingSettings(array $data, int $actorId, ?string $ip = null, ?string $agent = null): array
    {
        $old = $this->repository->accountingSettings();
        try {
            $settings = $this->repository->updateAccountingSettings($data, $actorId);
        } catch (\PDOException $exception) {
            throw new ValidationException([['code' => 'invalid_finance_account', 'detail' => 'One or more accounting mappings reference an invalid Chart of Accounts entry.']]);
        }
        $this->audit->log($actorId, 'inventory.accounting.updated', 'inventory_accounting_settings', 1, $old, $settings, $ip, $agent);
        return $settings;
    }

    public function financePostings(): array
    {
        return $this->repository->financePostings();
    }

    public function retryFinancePosting(int $id, int $actorId): array
    {
        return $this->finance->retry($id, $actorId);
    }
}
