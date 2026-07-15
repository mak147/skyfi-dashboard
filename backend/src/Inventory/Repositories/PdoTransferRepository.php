<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Repositories;

use PDO;
use SkyFi\Inventory\Contracts\TransferRepositoryContract;
use SkyFi\Inventory\DomainModels\WarehouseTransfer;
use SkyFi\Inventory\DTOs\TransferData;
use SkyFi\Inventory\DTOs\TransferListFilters;

final class PdoTransferRepository implements TransferRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(TransferListFilters $filters): array
    {
        $where = ['1 = 1'];
        $parameters = [];
        if ($filters->search !== null) {
            $where[] = '(t.transfer_number LIKE ? OR sw.name LIKE ? OR dw.name LIKE ?)';
            $like = '%' . $filters->search . '%';
            array_push($parameters, $like, $like, $like);
        }
        foreach ([['t.status', $filters->status], ['t.source_warehouse_id', $filters->sourceWarehouseId], ['t.destination_warehouse_id', $filters->destinationWarehouseId]] as [$column, $value]) {
            if ($value !== null) {
                $where[] = $column . ' = ?';
                $parameters[] = $value;
            }
        }
        $clause = implode(' AND ', $where);
        $count = $this->pdo->prepare("SELECT COUNT(*) FROM inventory_warehouse_transfers t JOIN warehouses sw ON sw.id = t.source_warehouse_id JOIN warehouses dw ON dw.id = t.destination_warehouse_id WHERE {$clause}");
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();
        $sorts = ['transfer_number' => 't.transfer_number', 'status' => 't.status', 'requested_at' => 't.requested_at', 'expected_at' => 't.expected_at'];
        $direction = str_starts_with($filters->sort, '-') ? 'DESC' : 'ASC';
        $order = $sorts[ltrim($filters->sort, '-')] ?? 't.requested_at';
        $offset = ($filters->page - 1) * $filters->perPage;
        $statement = $this->pdo->prepare("SELECT t.*, sw.name AS source_warehouse_name, sw.code AS source_warehouse_code, dw.name AS destination_warehouse_name, dw.code AS destination_warehouse_code,
            ru.name AS requested_by_name, au.name AS approved_by_name, du.name AS dispatched_by_name, rcu.name AS received_by_name,
            COUNT(l.id) AS line_count, COALESCE(SUM(l.quantity_requested), 0) AS total_requested, COALESCE(SUM(l.quantity_dispatched), 0) AS total_dispatched, COALESCE(SUM(l.quantity_received), 0) AS total_received
            FROM inventory_warehouse_transfers t JOIN warehouses sw ON sw.id = t.source_warehouse_id JOIN warehouses dw ON dw.id = t.destination_warehouse_id
            JOIN users ru ON ru.id = t.requested_by LEFT JOIN users au ON au.id = t.approved_by LEFT JOIN users du ON du.id = t.dispatched_by LEFT JOIN users rcu ON rcu.id = t.received_by
            LEFT JOIN inventory_warehouse_transfer_lines l ON l.transfer_id = t.id WHERE {$clause} GROUP BY t.id ORDER BY {$order} {$direction} LIMIT {$filters->perPage} OFFSET {$offset}");
        $statement->execute($parameters);
        $items = array_map(static fn(array $row): WarehouseTransfer => WarehouseTransfer::fromRow($row), $statement->fetchAll(PDO::FETCH_ASSOC));
        return ['items' => $items, 'total' => $total, 'page' => $filters->page, 'perPage' => $filters->perPage, 'lastPage' => max(1, (int) ceil($total / $filters->perPage))];
    }

    public function find(int $id, bool $forUpdate = false): ?WarehouseTransfer
    {
        $statement = $this->pdo->prepare("SELECT t.*, sw.name AS source_warehouse_name, sw.code AS source_warehouse_code, dw.name AS destination_warehouse_name, dw.code AS destination_warehouse_code,
            ru.name AS requested_by_name, au.name AS approved_by_name, du.name AS dispatched_by_name, rcu.name AS received_by_name
            FROM inventory_warehouse_transfers t JOIN warehouses sw ON sw.id = t.source_warehouse_id JOIN warehouses dw ON dw.id = t.destination_warehouse_id JOIN users ru ON ru.id = t.requested_by
            LEFT JOIN users au ON au.id = t.approved_by LEFT JOIN users du ON du.id = t.dispatched_by LEFT JOIN users rcu ON rcu.id = t.received_by WHERE t.id = ?" . ($forUpdate ? ' FOR UPDATE' : ''));
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $lines = $this->pdo->prepare("SELECT l.*, p.sku, p.name AS product_name, p.tracking_mode, sl.code AS source_location_code, sl.name AS source_location_name, dl.code AS destination_location_code, dl.name AS destination_location_name
            FROM inventory_warehouse_transfer_lines l JOIN inventory_products p ON p.id = l.product_id JOIN warehouse_locations sl ON sl.id = l.source_location_id JOIN warehouse_locations dl ON dl.id = l.destination_location_id WHERE l.transfer_id = ? ORDER BY l.id");
        $lines->execute([$id]);
        $row['lines'] = $lines->fetchAll(PDO::FETCH_ASSOC);
        $assets = $this->pdo->prepare('SELECT ta.transfer_line_id, a.id, a.asset_tag, a.serial_number, ta.dispatched_at, ta.received_at FROM inventory_warehouse_transfer_assets ta JOIN inventory_assets a ON a.id = ta.asset_id JOIN inventory_warehouse_transfer_lines l ON l.id = ta.transfer_line_id WHERE l.transfer_id = ? ORDER BY a.asset_tag');
        $assets->execute([$id]);
        $byLine = [];
        foreach ($assets->fetchAll(PDO::FETCH_ASSOC) as $asset) {
            $byLine[(int) $asset['transfer_line_id']][] = $asset;
        }
        foreach ($row['lines'] as &$line) {
            $line['assets'] = $byLine[(int) $line['id']] ?? [];
        }
        return WarehouseTransfer::fromRow($row);
    }

    public function create(TransferData $data, int $actorId): WarehouseTransfer
    {
        return $this->transaction(function () use ($data, $actorId): WarehouseTransfer {
            $this->pdo->prepare("INSERT INTO inventory_warehouse_transfers (transfer_number, source_warehouse_id, destination_warehouse_id, status, requested_by, requested_at, expected_at, notes) VALUES (?, ?, ?, 'draft', ?, UTC_TIMESTAMP(), ?, ?)")->execute([$this->number(), $data->sourceWarehouseId, $data->destinationWarehouseId, $actorId, $data->expectedAt, $data->notes]);
            $id = (int) $this->pdo->lastInsertId();
            $this->replaceLines($id, $data->lines);
            return $this->find($id) ?? throw new \RuntimeException('Unable to load created transfer.');
        });
    }

    public function update(int $id, TransferData $data, int $actorId): WarehouseTransfer
    {
        return $this->transaction(function () use ($id, $data): WarehouseTransfer {
            $this->pdo->prepare('UPDATE inventory_warehouse_transfers SET source_warehouse_id = ?, destination_warehouse_id = ?, expected_at = ?, notes = ? WHERE id = ?')->execute([$data->sourceWarehouseId, $data->destinationWarehouseId, $data->expectedAt, $data->notes, $id]);
            $this->pdo->prepare('DELETE FROM inventory_warehouse_transfer_lines WHERE transfer_id = ?')->execute([$id]);
            $this->replaceLines($id, $data->lines);
            return $this->find($id) ?? throw new \RuntimeException('Unable to load updated transfer.');
        });
    }

    public function transition(int $id, string $action, array $data, int $actorId): WarehouseTransfer
    {
        if ($action === 'submit') {
            $this->pdo->prepare("UPDATE inventory_warehouse_transfers SET status = 'pending' WHERE id = ?")->execute([$id]);
        } elseif ($action === 'approve') {
            $this->pdo->prepare("UPDATE inventory_warehouse_transfers SET status = 'approved', approved_by = ?, approved_at = UTC_TIMESTAMP() WHERE id = ?")->execute([$actorId, $id]);
        } elseif ($action === 'dispatch') {
            $quantities = is_array($data['lines'] ?? null) ? $data['lines'] : [];
            foreach ($quantities as $line) {
                if (!is_array($line)) {
                    continue;
                }
                $lineId = (int) ($line['id'] ?? 0);
                $quantity = number_format((float) ($line['quantity_dispatched'] ?? $line['quantity_requested'] ?? 0), 4, '.', '');
                $unitCost = number_format((float) ($line['unit_cost'] ?? 0), 4, '.', '');
                $this->pdo->prepare('UPDATE inventory_warehouse_transfer_lines SET quantity_dispatched = ?, unit_cost = ? WHERE id = ? AND transfer_id = ?')->execute([$quantity, $unitCost, $lineId, $id]);
                $this->markAssets($lineId, is_array($line['asset_ids'] ?? null) ? $line['asset_ids'] : [], 'dispatched_at');
            }
            $this->pdo->prepare("UPDATE inventory_warehouse_transfers SET status = 'in_transit', dispatched_by = ?, dispatched_at = UTC_TIMESTAMP() WHERE id = ?")->execute([$actorId, $id]);
        } elseif ($action === 'receive') {
            $quantities = is_array($data['lines'] ?? null) ? $data['lines'] : [];
            foreach ($quantities as $line) {
                if (!is_array($line)) {
                    continue;
                }
                $lineId = (int) ($line['id'] ?? 0);
                $quantity = number_format((float) ($line['quantity_received'] ?? $line['quantity_dispatched'] ?? 0), 4, '.', '');
                $this->pdo->prepare('UPDATE inventory_warehouse_transfer_lines SET quantity_received = ? WHERE id = ? AND transfer_id = ?')->execute([$quantity, $lineId, $id]);
                $this->markAssets($lineId, is_array($line['asset_ids'] ?? null) ? $line['asset_ids'] : [], 'received_at');
            }
            $pending = $this->pdo->prepare('SELECT COUNT(*) FROM inventory_warehouse_transfer_lines WHERE transfer_id = ? AND quantity_received < quantity_dispatched');
            $pending->execute([$id]);
            $status = (int) $pending->fetchColumn() > 0 ? 'partially_received' : 'completed';
            $this->pdo->prepare('UPDATE inventory_warehouse_transfers SET status = ?, received_by = ?, received_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$status, $actorId, $id]);
        } elseif ($action === 'cancel') {
            $this->pdo->prepare("UPDATE inventory_warehouse_transfers SET status = 'cancelled', cancellation_reason = ? WHERE id = ?")->execute([trim((string) ($data['reason'] ?? '')), $id]);
        }
        return $this->find($id) ?? throw new \RuntimeException('Unable to load transfer.');
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM inventory_warehouse_transfers WHERE id = ?')->execute([$id]);
    }

    public function transaction(callable $callback): mixed
    {
        $owns = !$this->pdo->inTransaction();
        if ($owns) {
            $this->pdo->beginTransaction();
        }
        try {
            $result = $callback();
            if ($owns) {
                $this->pdo->commit();
            }
            return $result;
        } catch (\Throwable $exception) {
            if ($owns && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    /** @param array<int, array<string, mixed>> $lines */
    private function replaceLines(int $transferId, array $lines): void
    {
        $lineStatement = $this->pdo->prepare('INSERT INTO inventory_warehouse_transfer_lines (transfer_id, product_id, source_location_id, destination_location_id, quantity_requested, notes) VALUES (?, ?, ?, ?, ?, ?)');
        $assetStatement = $this->pdo->prepare('INSERT INTO inventory_warehouse_transfer_assets (transfer_line_id, asset_id) VALUES (?, ?)');
        foreach ($lines as $line) {
            $lineStatement->execute([
                $transferId, (int) $line['product_id'], (int) $line['source_location_id'], (int) $line['destination_location_id'],
                number_format((float) $line['quantity_requested'], 4, '.', ''), trim((string) ($line['notes'] ?? '')) ?: null,
            ]);
            $lineId = (int) $this->pdo->lastInsertId();
            foreach (array_unique(array_map('intval', is_array($line['asset_ids'] ?? null) ? $line['asset_ids'] : [])) as $assetId) {
                if ($assetId > 0) {
                    $assetStatement->execute([$lineId, $assetId]);
                }
            }
        }
    }

    /** @param array<int, mixed> $assetIds */
    private function markAssets(int $lineId, array $assetIds, string $column): void
    {
        if (!in_array($column, ['dispatched_at', 'received_at'], true)) {
            return;
        }
        $ids = array_values(array_filter(array_unique(array_map('intval', $assetIds)), static fn(int $id): bool => $id > 0));
        if ($ids === []) {
            return;
        }
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $this->pdo->prepare("UPDATE inventory_warehouse_transfer_assets SET {$column} = UTC_TIMESTAMP() WHERE transfer_line_id = ? AND asset_id IN ({$placeholders})")->execute([$lineId, ...$ids]);
    }

    private function number(): string
    {
        return sprintf('TRF-%s-%s', gmdate('YmdHis'), strtoupper(bin2hex(random_bytes(3))));
    }
}
