<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Repositories;

use PDO;
use SkyFi\Vendors\Contracts\VendorQuotationRepositoryContract;
use SkyFi\Vendors\DomainModels\VendorQuotation;
use SkyFi\Vendors\DTOs\VendorQuotationData;

final class PdoVendorQuotationRepository implements VendorQuotationRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listByVendor(?int $vendorId = null): array
    {
        if ($vendorId !== null && $vendorId > 0) {
            $stmt = $this->pdo->prepare(
                'SELECT vq.*, v.name AS vendor_name, pr.request_number AS purchase_request_number
                 FROM vendor_quotations vq
                 JOIN vendors v ON v.id = vq.vendor_id
                 LEFT JOIN purchase_requests pr ON pr.id = vq.purchase_request_id
                 WHERE vq.vendor_id = ? AND vq.deleted_at IS NULL
                 ORDER BY vq.quotation_date DESC, vq.id DESC'
            );
            $stmt->execute([$vendorId]);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT vq.*, v.name AS vendor_name, pr.request_number AS purchase_request_number
                 FROM vendor_quotations vq
                 JOIN vendors v ON v.id = vq.vendor_id
                 LEFT JOIN purchase_requests pr ON pr.id = vq.purchase_request_id
                 WHERE vq.deleted_at IS NULL
                 ORDER BY vq.quotation_date DESC, vq.id DESC'
            );
            $stmt->execute();
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Populate item counts / items summary
        foreach ($rows as &$row) {
            $row['items'] = $this->getItems((int) $row['id']);
            $row['item_count'] = count($row['items']);
        }
        unset($row);

        return array_map(static fn(array $r) => VendorQuotation::fromRow($r), $rows);
    }

    public function find(int $id): ?VendorQuotation
    {
        $stmt = $this->pdo->prepare(
            'SELECT vq.*, v.name AS vendor_name, pr.request_number AS purchase_request_number
             FROM vendor_quotations vq
             JOIN vendors v ON v.id = vq.vendor_id
             LEFT JOIN purchase_requests pr ON pr.id = vq.purchase_request_id
             WHERE vq.id = ? AND vq.deleted_at IS NULL'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $row['items'] = $this->getItems($id);
        $row['item_count'] = count($row['items']);

        return VendorQuotation::fromRow($row);
    }

    public function create(VendorQuotationData $data, int $actorId): VendorQuotation
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO vendor_quotations (vendor_id, purchase_request_id, rfq_number, quotation_number, quotation_date, validity_date, total_amount, currency, status, notes, created_by, updated_by, created_at, updated_at)
             VALUES (:vid, :prid, :rfq, :num, :qdate, :vdate, :total, :cur, :status, :notes, :cb, :ub, :cat, :uat)'
        );
        $stmt->execute([
            'vid' => $data->vendorId,
            'prid' => $data->purchaseRequestId,
            'rfq' => $data->rfqNumber,
            'num' => $data->quotationNumber,
            'qdate' => $data->quotationDate,
            'vdate' => $data->validityDate,
            'total' => $data->totalAmount,
            'cur' => $data->currency,
            'status' => $data->status,
            'notes' => $data->notes,
            'cb' => $actorId,
            'ub' => $actorId,
            'cat' => $now,
            'uat' => $now,
        ]);
        $id = (int) $this->pdo->lastInsertId();

        if ($data->items !== []) {
            $itemStmt = $this->pdo->prepare(
                'INSERT INTO vendor_quotation_items (quotation_id, product_id, description, quantity, unit_price, line_total, notes)
                 VALUES (:qid, :pid, :desc, :qty, :up, :total, :notes)'
            );
            foreach ($data->items as $item) {
                $pid = isset($item['product_id']) && is_numeric($item['product_id']) && ((int) $item['product_id'] > 0) ? (int) $item['product_id'] : null;
                $qty = (float) ($item['quantity'] ?? 1);
                $up = (float) ($item['unit_price'] ?? 0);
                $lineTotal = (float) ($item['line_total'] ?? ($qty * $up));
                $itemStmt->execute([
                    'qid' => $id,
                    'pid' => $pid,
                    'desc' => trim((string) ($item['description'] ?? 'Item')),
                    'qty' => $qty,
                    'up' => $up,
                    'total' => $lineTotal,
                    'notes' => isset($item['notes']) && is_string($item['notes']) ? trim($item['notes']) : null,
                ]);
            }
        }

        return $this->find($id) ?? throw new \RuntimeException('Failed to load created quotation.');
    }

    public function updateStatus(int $id, string $status, int $actorId): VendorQuotation
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE vendor_quotations SET status = ?, updated_by = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$status, $actorId, $now, $id]);
        return $this->find($id) ?? throw new \RuntimeException('Quotation not found.');
    }

    public function delete(int $id, int $actorId): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE vendor_quotations SET deleted_at = ?, updated_by = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$now, $actorId, $now, $id]);
    }

    /** @return array<int, array<string, mixed>> */
    private function getItems(int $quotationId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT vqi.*, p.name AS product_name, p.sku AS product_sku
             FROM vendor_quotation_items vqi
             LEFT JOIN inventory_products p ON p.id = vqi.product_id
             WHERE vqi.quotation_id = ?
             ORDER BY vqi.id ASC'
        );
        $stmt->execute([$quotationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
