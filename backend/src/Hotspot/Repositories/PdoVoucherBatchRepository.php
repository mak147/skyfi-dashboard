<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Repositories;

use PDO;
use SkyFi\Hotspot\Contracts\VoucherBatchRepositoryContract;
use SkyFi\Hotspot\DomainModels\VoucherBatch;
use SkyFi\Shared\Exceptions\NotFoundException;

final class PdoVoucherBatchRepository implements VoucherBatchRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(int $page = 1, int $perPage = 15, ?string $status = null): array
    {
        $where = ['deleted_at IS NULL'];
        $params = [];

        if ($status !== null) {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) FROM hotspot_voucher_batches WHERE {$whereSql}";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $lastPage = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        $selectSql = "SELECT * FROM hotspot_voucher_batches WHERE {$whereSql} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->pdo->prepare($selectSql);
        $stmt->execute($params);

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = VoucherBatch::fromRow($row);
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ];
    }

    public function find(int $id): ?VoucherBatch
    {
        $stmt = $this->pdo->prepare('SELECT * FROM hotspot_voucher_batches WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? VoucherBatch::fromRow($row) : null;
    }

    public function existsByBatchCode(string $batchCode): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM hotspot_voucher_batches WHERE batch_code = :batch_code');
        $stmt->execute(['batch_code' => $batchCode]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function insert(array $data): VoucherBatch
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(static fn ($k): string => ":{$k}", array_keys($data)));

        $sql = "INSERT INTO hotspot_voucher_batches ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        $inserted = $this->find((int) $this->pdo->lastInsertId());
        if ($inserted === null) {
            throw new NotFoundException('Failed to retrieve inserted voucher batch.');
        }

        return $inserted;
    }

    public function update(int $id, array $data): VoucherBatch
    {
        if ($data === []) {
            $existing = $this->find($id);
            return $existing ?? throw new NotFoundException('Voucher batch not found.');
        }

        $sets = implode(', ', array_map(static fn ($k): string => "{$k} = :{$k}", array_keys($data)));
        $sql = "UPDATE hotspot_voucher_batches SET {$sets} WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([...$data, 'id' => $id]);

        $updated = $this->find($id);
        if ($updated === null) {
            throw new NotFoundException('Voucher batch not found.');
        }

        return $updated;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE hotspot_voucher_batches SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
