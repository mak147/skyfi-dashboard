<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Services;

use SkyFi\Inventory\Contracts\StockRepositoryContract;
use SkyFi\Inventory\Contracts\TransferRepositoryContract;
use SkyFi\Inventory\DomainModels\WarehouseTransfer;
use SkyFi\Inventory\DTOs\StockOperationData;
use SkyFi\Inventory\DTOs\TransferData;
use SkyFi\Inventory\DTOs\TransferListFilters;
use SkyFi\Inventory\Validators\TransferValidator;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Events\EventDispatcher;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class TransferService
{
    public function __construct(
        private readonly TransferRepositoryContract $repository,
        private readonly StockRepositoryContract $stock,
        private readonly TransferValidator $validator,
        private readonly AuditLoggerContract $audit,
    ) {
    }

    public function list(TransferListFilters $filters): array
    {
        return $this->repository->list($filters);
    }

    public function get(int $id): WarehouseTransfer
    {
        return $this->repository->find($id) ?? throw new NotFoundException('Warehouse transfer not found.');
    }

    public function create(TransferData $data, int $actorId, ?string $ip = null, ?string $agent = null): WarehouseTransfer
    {
        $this->validator->validate($data);
        try {
            $transfer = $this->repository->create($data, $actorId);
        } catch (\PDOException $exception) {
            throw new ValidationException([['code' => 'invalid_transfer_reference', 'detail' => 'A warehouse, location, product, or asset reference is invalid or duplicated.']]);
        }
        $this->audit->log($actorId, 'inventory.transfer.created', 'inventory_transfer', $transfer->id(), null, $transfer->toArray(), $ip, $agent);
        return $transfer;
    }

    public function update(int $id, TransferData $data, int $actorId, ?string $ip = null, ?string $agent = null): WarehouseTransfer
    {
        $old = $this->get($id);
        $this->requireStatus($old, ['draft'], 'Only draft transfers can be edited.');
        $this->validator->validate($data);
        try {
            $transfer = $this->repository->update($id, $data, $actorId);
        } catch (\PDOException $exception) {
            throw new ValidationException([['code' => 'invalid_transfer_reference', 'detail' => 'A warehouse, location, product, or asset reference is invalid or duplicated.']]);
        }
        $this->audit->log($actorId, 'inventory.transfer.updated', 'inventory_transfer', $id, $old->toArray(), $transfer->toArray(), $ip, $agent);
        return $transfer;
    }

    public function delete(int $id, int $actorId, ?string $ip = null, ?string $agent = null): void
    {
        $transfer = $this->get($id);
        $this->requireStatus($transfer, ['draft'], 'Only draft transfers can be deleted.');
        $this->repository->delete($id);
        $this->audit->log($actorId, 'inventory.transfer.deleted', 'inventory_transfer', $id, $transfer->toArray(), null, $ip, $agent);
    }

    public function submit(int $id, int $actorId, ?string $ip = null, ?string $agent = null): WarehouseTransfer
    {
        return $this->simpleTransition($id, 'submit', ['draft'], [], $actorId, $ip, $agent);
    }

    public function approve(int $id, int $actorId, ?string $ip = null, ?string $agent = null): WarehouseTransfer
    {
        return $this->simpleTransition($id, 'approve', ['pending'], [], $actorId, $ip, $agent);
    }

    public function cancel(int $id, string $reason, int $actorId, ?string $ip = null, ?string $agent = null): WarehouseTransfer
    {
        if (trim($reason) === '') {
            throw new ValidationException([['code' => 'validation_error', 'detail' => 'A cancellation reason is required.', 'source' => ['pointer' => '/data/attributes/reason']]]);
        }
        return $this->simpleTransition($id, 'cancel', ['draft', 'pending', 'approved'], ['reason' => trim($reason)], $actorId, $ip, $agent);
    }

    public function dispatch(int $id, array $data, int $actorId, ?string $ip = null, ?string $agent = null): WarehouseTransfer
    {
        $old = $this->get($id);
        $this->requireStatus($old, ['approved'], 'Only approved transfers can be dispatched.');
        $attributes = $old->toArray();
        $requested = $this->linePayloads($data);
        $movement = null;
        $transfer = $this->stock->transaction(function () use ($id, $attributes, $requested, $actorId, &$movement): WarehouseTransfer {
            $locked = $this->repository->find($id, true) ?? throw new NotFoundException('Warehouse transfer not found.');
            $this->requireStatus($locked, ['approved'], 'Only approved transfers can be dispatched.');
            $attributes = $locked->toArray();
            $stockLines = [];
            $transitionLines = [];
            foreach ($attributes['lines'] as $line) {
                $override = $requested[(int) $line['id']] ?? [];
                $quantity = (float) ($override['quantity_dispatched'] ?? $line['quantity_requested']);
                if ($quantity <= 0 || $quantity > (float) $line['quantity_requested']) {
                    throw new ValidationException([['code' => 'dispatch_quantity_invalid', 'detail' => 'Dispatched quantity must be positive and cannot exceed the requested quantity.']]);
                }
                $assetIds = $this->assetIds($line, $override, false);
                if ($line['tracking_mode'] === 'serialized' && count($assetIds) !== (int) $quantity) {
                    throw new ValidationException([['code' => 'serialized_dispatch_mismatch', 'detail' => 'Dispatched serialized quantity must match selected assets.']]);
                }
                $stockLines[] = ['product_id' => (int) $line['product_id'], 'source_location_id' => (int) $line['source_location_id'], 'quantity' => $quantity, 'asset_ids' => $assetIds];
                $transitionLines[] = ['id' => (int) $line['id'], 'quantity_dispatched' => $quantity, 'quantity_requested' => $line['quantity_requested'], 'asset_ids' => $assetIds];
            }
            $movement = $this->stock->post(StockOperationData::fromArray('transfer_dispatch', [
                'reference_type' => 'warehouse_transfer', 'reference_number' => $attributes['transfer_number'], 'notes' => $attributes['notes'], 'lines' => $stockLines,
            ]), $actorId);
            $costs = [];
            foreach ($movement->toArray()['lines'] as $line) {
                $key = $line['product_id'] . ':' . $line['source_location_id'];
                $costs[$key] = $line['unit_cost'];
            }
            foreach ($transitionLines as &$line) {
                $source = null;
                foreach ($attributes['lines'] as $original) {
                    if ((int) $original['id'] === (int) $line['id']) {
                        $source = $original;
                        break;
                    }
                }
                $line['unit_cost'] = $source !== null ? ($costs[$source['product_id'] . ':' . $source['source_location_id']] ?? 0) : 0;
            }
            return $this->repository->transition($id, 'dispatch', ['lines' => $transitionLines], $actorId);
        });
        $this->audit->log($actorId, 'inventory.transfer.dispatched', 'inventory_transfer', $id, $old->toArray(), $transfer->toArray(), $ip, $agent);
        EventDispatcher::dispatch('inventory.transfer.dispatched', ['transfer' => $transfer->toArray(), 'movement' => $movement?->toArray()]);
        return $transfer;
    }

    public function receive(int $id, array $data, int $actorId, ?string $ip = null, ?string $agent = null): WarehouseTransfer
    {
        $old = $this->get($id);
        $this->requireStatus($old, ['in_transit', 'partially_received'], 'Only in-transit transfers can be received.');
        $attributes = $old->toArray();
        $requested = $this->linePayloads($data);
        $movement = null;
        $transfer = $this->stock->transaction(function () use ($id, $attributes, $requested, $actorId, &$movement): WarehouseTransfer {
            $locked = $this->repository->find($id, true) ?? throw new NotFoundException('Warehouse transfer not found.');
            $this->requireStatus($locked, ['in_transit', 'partially_received'], 'Only in-transit transfers can be received.');
            $attributes = $locked->toArray();
            $stockLines = [];
            $transitionLines = [];
            foreach ($attributes['lines'] as $line) {
                $remaining = (float) $line['quantity_dispatched'] - (float) $line['quantity_received'];
                if ($remaining <= 0) {
                    continue;
                }
                $override = $requested[(int) $line['id']] ?? [];
                $receiptQuantity = (float) ($override['quantity_received'] ?? $remaining);
                if ($receiptQuantity <= 0 || $receiptQuantity > $remaining) {
                    throw new ValidationException([['code' => 'receipt_quantity_invalid', 'detail' => 'Receipt quantity must be positive and cannot exceed quantity in transit.']]);
                }
                $assetIds = $this->assetIds($line, $override, true);
                if ($line['tracking_mode'] === 'serialized' && count($assetIds) !== (int) $receiptQuantity) {
                    throw new ValidationException([['code' => 'serialized_receipt_mismatch', 'detail' => 'Received serialized quantity must match selected assets.']]);
                }
                $stockLines[] = ['product_id' => (int) $line['product_id'], 'destination_location_id' => (int) $line['destination_location_id'], 'quantity' => $receiptQuantity, 'asset_ids' => $assetIds, 'unit_cost' => $line['unit_cost']];
                $transitionLines[] = ['id' => (int) $line['id'], 'quantity_received' => (float) $line['quantity_received'] + $receiptQuantity, 'quantity_dispatched' => $line['quantity_dispatched'], 'asset_ids' => $assetIds];
            }
            if ($stockLines === []) {
                throw new ValidationException([['code' => 'nothing_to_receive', 'detail' => 'No inventory remains to be received.']]);
            }
            $movement = $this->stock->post(StockOperationData::fromArray('transfer_receipt', [
                'reference_type' => 'warehouse_transfer', 'reference_number' => $attributes['transfer_number'], 'notes' => $attributes['notes'], 'lines' => $stockLines,
            ]), $actorId);
            return $this->repository->transition($id, 'receive', ['lines' => $transitionLines], $actorId);
        });
        $this->audit->log($actorId, 'inventory.transfer.received', 'inventory_transfer', $id, $old->toArray(), $transfer->toArray(), $ip, $agent);
        EventDispatcher::dispatch('inventory.transfer.received', ['transfer' => $transfer->toArray(), 'movement' => $movement?->toArray()]);
        return $transfer;
    }

    private function simpleTransition(int $id, string $action, array $statuses, array $data, int $actorId, ?string $ip, ?string $agent): WarehouseTransfer
    {
        $old = $this->get($id);
        $this->requireStatus($old, $statuses, 'Transfer cannot perform this action in its current state.');
        $transfer = $this->repository->transition($id, $action, $data, $actorId);
        $this->audit->log($actorId, 'inventory.transfer.' . $action, 'inventory_transfer', $id, $old->toArray(), $transfer->toArray(), $ip, $agent);
        return $transfer;
    }

    private function requireStatus(WarehouseTransfer $transfer, array $statuses, string $message): void
    {
        if (!in_array($transfer->status(), $statuses, true)) {
            throw new ValidationException([['code' => 'invalid_transfer_transition', 'detail' => $message]]);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function linePayloads(array $data): array
    {
        $result = [];
        foreach (is_array($data['lines'] ?? null) ? $data['lines'] : [] as $line) {
            if (is_array($line) && (int) ($line['id'] ?? 0) > 0) {
                $result[(int) $line['id']] = $line;
            }
        }
        return $result;
    }

    /** @param array<string, mixed> $line @param array<string, mixed> $override @return array<int, int> */
    private function assetIds(array $line, array $override, bool $receiving): array
    {
        if (is_array($override['asset_ids'] ?? null)) {
            return array_values(array_unique(array_map('intval', $override['asset_ids'])));
        }
        $ids = [];
        foreach ($line['assets'] ?? [] as $asset) {
            if (!$receiving || ($asset['dispatched_at'] !== null && $asset['received_at'] === null)) {
                $ids[] = (int) $asset['id'];
            }
        }
        return $ids;
    }
}
