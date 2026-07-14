<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Repositories;

use PDO;
use SkyFi\Hotspot\Contracts\VoucherRepositoryContract;
use SkyFi\Hotspot\DomainModels\Voucher;
use SkyFi\Hotspot\DTOs\VoucherListFilters;
use SkyFi\Shared\Exceptions\NotFoundException;

final class PdoVoucherRepository implements VoucherRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(VoucherListFilters $filters): array
    {
        $where = ['v.deleted_at IS NULL'];
        $params = [];

        if ($filters->status !== null) {
            $where[] = 'v.status = :status';
            $params['status'] = $filters->status;
        }

        if ($filters->batchId !== null) {
            $where[] = 'v.batch_id = :batch_id';
            $params['batch_id'] = $filters->batchId;
        }

        if ($filters->routerId !== null) {
            $where[] = 'b.router_id = :router_id';
            $params['router_id'] = $filters->routerId;
        }

        if ($filters->search !== null) {
            $where[] = '(v.code LIKE :search)';
            $params['search'] = '%' . $filters->search . '%';
        }

        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) FROM hotspot_vouchers v LEFT JOIN hotspot_voucher_batches b ON v.batch_id = b.id WHERE {$whereSql}";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $lastPage = max(1, (int) ceil($total / $filters->perPage));
        $offset = ($filters->page - 1) * $filters->perPage;

        $sortField = ltrim($filters->sort, '-');
        $sortOrder = str_starts_with($filters->sort, '-') ? 'DESC' : 'ASC';

        $allowedSorts = ['id', 'code', 'status', 'created_at', 'expires_at', 'used_at'];
        if (!in_array($sortField, $allowedSorts, true)) {
            $sortField = 'created_at';
            $sortOrder = 'DESC';
        }

        $selectSql = "SELECT v.* FROM hotspot_vouchers v LEFT JOIN hotspot_voucher_batches b ON v.batch_id = b.id WHERE {$whereSql} ORDER BY v.{$sortField} {$sortOrder} LIMIT {$filters->perPage} OFFSET {$offset}";
        $stmt = $this->pdo->prepare($selectSql);
        $stmt->execute($params);

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = Voucher::fromRow($row);
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $filters->page,
            'perPage' => $filters->perPage,
            'lastPage' => $lastPage,
        ];
    }

    public function find(int $id): ?Voucher
    {
        $stmt = $this->pdo->prepare('SELECT * FROM hotspot_vouchers WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? Voucher::fromRow($row) : null;
    }

    public function findByCode(string $code): ?Voucher
    {
        $stmt = $this->pdo->prepare('SELECT * FROM hotspot_vouchers WHERE code = :code AND deleted_at IS NULL');
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? Voucher::fromRow($row) : null;
    }

    public function insert(array $data): Voucher
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(static fn ($k): string => ":{$k}", array_keys($data)));

        $sql = "INSERT INTO hotspot_vouchers ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        $inserted = $this->find((int) $this->pdo->lastInsertId());
        if ($inserted === null) {
            throw new NotFoundException('Failed to retrieve inserted voucher.');
        }

        return $inserted;
    }

    public function update(int $id, array $data): Voucher
    {
        if ($data === []) {
            $existing = $this->find($id);
            return $existing ?? throw new NotFoundException('Voucher not found.');
        }

        $sets = implode(', ', array_map(static fn ($k): string => "{$k} = :{$k}", array_keys($data)));
        $sql = "UPDATE hotspot_vouchers SET {$sets} WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([...$data, 'id' => $id]);

        $updated = $this->find($id);
        if ($updated === null) {
            throw new NotFoundException('Voucher not found.');
        }

        return $updated;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE hotspot_vouchers SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM hotspot_vouchers WHERE status = :status AND deleted_at IS NULL");
        $stmt->execute(['status' => $status]);
        return (int) $stmt->fetchColumn();
    }

    public function countExpired(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM hotspot_vouchers WHERE (status = 'expired' OR (expires_at IS NOT NULL AND expires_at < NOW())) AND deleted_at IS NULL");
        return (int) $stmt->fetchColumn();
    }

    public function countDailyLogins(?string $date = null): int
    {
        $targetDate = $date ?? gmdate('Y-m-d');
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM hotspot_vouchers WHERE status = 'used' AND DATE(used_at) = :date AND deleted_at IS NULL");
        $stmt->execute(['date' => $targetDate]);
        return (int) $stmt->fetchColumn();
    }
}
