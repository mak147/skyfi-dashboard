<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Repositories;

use PDO;
use SkyFi\Purchasing\Contracts\PurchaseRequestRepositoryContract;
use SkyFi\Purchasing\DomainModels\PurchaseRequest;
use SkyFi\Purchasing\DTOs\PurchaseRequestData;
use SkyFi\Purchasing\DTOs\PurchaseRequestListFilters;

final class PdoPurchaseRequestRepository implements PurchaseRequestRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(PurchaseRequestListFilters $filters): array
    {
        $where = ['pr.deleted_at IS NULL'];
        $params = [];

        if ($filters->search !== null && $filters->search !== '') {
            $where[] = '(pr.request_number LIKE :search OR pr.notes LIKE :search2 OR pr.department LIKE :search3)';
            $params['search'] = $params['search2'] = $params['search3'] = '%' . $filters->search . '%';
        }
        if ($filters->status !== null) {
            $where[] = 'pr.status = :status';
            $params['status'] = $filters->status;
        }
        if ($filters->priority !== null) {
            $where[] = 'pr.priority = :priority';
            $params['priority'] = $filters->priority;
        }

        $whereSql = implode(' AND ', $where);
        $allowedSort = ['id', 'request_number', 'priority', 'status', 'required_date', 'created_at'];
        $sortBy = in_array($filters->sortBy, $allowedSort, true) ? $filters->sortBy : 'created_at';
        $sortDir = $filters->sortDir === 'asc' ? 'ASC' : 'DESC';

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM purchase_requests pr WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($filters->page - 1) * $filters->perPage;
        $sql = "SELECT pr.*, u.name AS requester_name, cu.name AS created_by_name
                FROM purchase_requests pr
                LEFT JOIN users u ON u.id = pr.requester_user_id
                LEFT JOIN users cu ON cu.id = pr.created_by
                WHERE {$whereSql}
                ORDER BY pr.{$sortBy} {$sortDir}
                LIMIT {$filters->perPage} OFFSET {$offset}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = array_map(static fn(array $row) => PurchaseRequest::fromRow(array_merge($row, [
            'item_count' => 0,
        ])), $rows);

        // Batch load item counts
        if ($items !== []) {
            $ids = array_map(static fn(PurchaseRequest $r) => $r->id(), $items);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $countSql = "SELECT purchase_request_id, COUNT(*) AS cnt, SUM(quantity) AS total_qty FROM purchase_request_items WHERE purchase_request_id IN ({$placeholders}) GROUP BY purchase_request_id";
            $countStmt2 = $this->pdo->prepare($countSql);
            $countStmt2->execute($ids);
            $counts = [];
            while ($c = $countStmt2->fetch(PDO::FETCH_ASSOC)) {
                $counts[(int) $c['purchase_request_id']] = ['cnt' => (int) $c['cnt'], 'qty' => (float) $c['total_qty']];
            }
            $items = array_map(static function (PurchaseRequest $r) use ($counts) {
                $arr = $r->toArray();
                $arr['item_count'] = $counts[$r->id()]['cnt'] ?? 0;
                $arr['total_quantity'] = $counts[$r->id()]['qty'] ?? 0;
                return PurchaseRequest::fromRow($arr);
            }, $items);
        }

        $lastPage = max(1, (int) ceil($total / $filters->perPage));
        return ['items' => $items, 'total' => $total, 'page' => $filters->page, 'perPage' => $filters->perPage, 'lastPage' => $lastPage];
    }

    public function find(int $id): ?PurchaseRequest
    {
        $stmt = $this->pdo->prepare(
            'SELECT pr.*, u.name AS requester_name, cu.name AS created_by_name
             FROM purchase_requests pr
             LEFT JOIN users u ON u.id = pr.requester_user_id
             LEFT JOIN users cu ON cu.id = pr.created_by
             WHERE pr.id = ? AND pr.deleted_at IS NULL'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? PurchaseRequest::fromRow(array_merge($row, ['items' => $this->getItems($id), 'approvals' => $this->getApprovals($id)])) : null;
    }

    public function create(PurchaseRequestData $data, int $actorId): PurchaseRequest
    {
        $number = $this->nextRequestNumber();
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO purchase_requests (request_number, requester_user_id, department, priority, required_date, status, notes, created_by, updated_by, created_at, updated_at)
             VALUES (:num, :req, :dept, :pri, :rdate, :status, :notes, :cb, :ub, :cat, :uat)'
        );
        $stmt->execute([
            'num' => $number,
            'req' => $data->requesterUserId,
            'dept' => $data->department,
            'pri' => $data->priority,
            'rdate' => $data->requiredDate,
            'status' => 'draft',
            'notes' => $data->notes,
            'cb' => $actorId,
            'ub' => $actorId,
            'cat' => $now,
            'uat' => $now,
        ]);
        $requestId = (int) $this->pdo->lastInsertId();

        $itemStmt = $this->pdo->prepare(
            'INSERT INTO purchase_request_items (purchase_request_id, product_id, description, quantity, estimated_unit_cost, notes) VALUES (?, ?, ?, ?, ?, ?)'
        );
        foreach ($data->items as $item) {
            $itemStmt->execute([
                $requestId,
                (int) ($item['product_id'] ?? 0),
                $item['description'] ?? null,
                (float) ($item['quantity'] ?? 0),
                (float) ($item['estimated_unit_cost'] ?? 0),
                $item['notes'] ?? null,
            ]);
        }

        return $this->find($requestId) ?? PurchaseRequest::fromRow(['id' => $requestId, 'request_number' => $number, 'status' => 'draft']);
    }

    public function update(int $id, PurchaseRequestData $data, int $actorId): PurchaseRequest
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE purchase_requests SET department = ?, priority = ?, required_date = ?, notes = ?, updated_by = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$data->department, $data->priority, $data->requiredDate, $data->notes, $actorId, $now, $id]);

        // Replace items
        $this->pdo->prepare('DELETE FROM purchase_request_items WHERE purchase_request_id = ?')->execute([$id]);
        $itemStmt = $this->pdo->prepare(
            'INSERT INTO purchase_request_items (purchase_request_id, product_id, description, quantity, estimated_unit_cost, notes) VALUES (?, ?, ?, ?, ?, ?)'
        );
        foreach ($data->items as $item) {
            $itemStmt->execute([
                $id,
                (int) ($item['product_id'] ?? 0),
                $item['description'] ?? null,
                (float) ($item['quantity'] ?? 0),
                (float) ($item['estimated_unit_cost'] ?? 0),
                $item['notes'] ?? null,
            ]);
        }

        return $this->find($id) ?? PurchaseRequest::fromRow(['id' => $id]);
    }

    public function updateStatus(int $id, string $status, int $actorId): PurchaseRequest
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE purchase_requests SET status = ?, updated_by = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$status, $actorId, $now, $id]);
        return $this->find($id) ?? PurchaseRequest::fromRow(['id' => $id, 'status' => $status]);
    }

    public function addApproval(int $requestId, int $approverId, string $decision, ?string $comments): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO purchase_request_approvals (purchase_request_id, approver_user_id, decision, comments, decided_at) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$requestId, $approverId, $decision, $comments, date('Y-m-d H:i:s')]);
    }

    public function nextRequestNumber(): string
    {
        $year = date('Y');
        $prefix = "PR-{$year}-";
        $stmt = $this->pdo->prepare("SELECT request_number FROM purchase_requests WHERE request_number LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$prefix . '%']);
        $last = $stmt->fetchColumn();
        $seq = 1;
        if ($last) {
            $parts = explode('-', (string) $last);
            $seq = (int) end($parts) + 1;
        }
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /** @return array<int, array<string, mixed>> */
    public function getApprovals(int $requestId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT pra.*, u.name AS approver_name FROM purchase_request_approvals pra LEFT JOIN users u ON u.id = pra.approver_user_id WHERE pra.purchase_request_id = ? ORDER BY pra.decided_at ASC'
        );
        $stmt->execute([$requestId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<int, array<string, mixed>> */
    public function getItems(int $requestId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT pri.*, p.name AS product_name, p.sku, u.name AS unit_name, u.symbol AS unit_symbol
             FROM purchase_request_items pri
             LEFT JOIN inventory_products p ON p.id = pri.product_id
             LEFT JOIN inventory_units u ON u.id = p.unit_id
             WHERE pri.purchase_request_id = ?
             ORDER BY pri.id ASC'
        );
        $stmt->execute([$requestId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
