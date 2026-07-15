<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Repositories;

use PDO;
use SkyFi\Vendors\Contracts\VendorContractRepositoryContract;
use SkyFi\Vendors\DomainModels\VendorContract;
use SkyFi\Vendors\DTOs\VendorContractData;

final class PdoVendorContractRepository implements VendorContractRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listByVendor(?int $vendorId = null): array
    {
        $this->syncExpiringContracts();

        if ($vendorId !== null && $vendorId > 0) {
            $stmt = $this->pdo->prepare(
                'SELECT vc.*, v.name AS vendor_name
                 FROM vendor_contracts vc
                 JOIN vendors v ON v.id = vc.vendor_id
                 WHERE vc.vendor_id = ? AND vc.deleted_at IS NULL
                 ORDER BY vc.end_date ASC'
            );
            $stmt->execute([$vendorId]);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT vc.*, v.name AS vendor_name
                 FROM vendor_contracts vc
                 JOIN vendors v ON v.id = vc.vendor_id
                 WHERE vc.deleted_at IS NULL
                 ORDER BY vc.end_date ASC'
            );
            $stmt->execute();
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(static fn(array $row) => VendorContract::fromRow($row), $rows);
    }

    public function find(int $id): ?VendorContract
    {
        $stmt = $this->pdo->prepare(
            'SELECT vc.*, v.name AS vendor_name
             FROM vendor_contracts vc
             JOIN vendors v ON v.id = vc.vendor_id
             WHERE vc.id = ? AND vc.deleted_at IS NULL'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? VendorContract::fromRow($row) : null;
    }

    public function create(VendorContractData $data, int $actorId): VendorContract
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO vendor_contracts (vendor_id, contract_number, title, start_date, end_date, renewal_date, contract_value, currency, status, attachment_path, notes, created_by, updated_by, created_at, updated_at)
             VALUES (:vid, :num, :title, :sdate, :edate, :rdate, :val, :cur, :status, :att, :notes, :cb, :ub, :cat, :uat)'
        );
        $stmt->execute([
            'vid' => $data->vendorId,
            'num' => $data->contractNumber,
            'title' => $data->title,
            'sdate' => $data->startDate,
            'edate' => $data->endDate,
            'rdate' => $data->renewalDate,
            'val' => $data->contractValue,
            'cur' => $data->currency,
            'status' => $data->status,
            'att' => $data->attachmentPath,
            'notes' => $data->notes,
            'cb' => $actorId,
            'ub' => $actorId,
            'cat' => $now,
            'uat' => $now,
        ]);
        $id = (int) $this->pdo->lastInsertId();
        return $this->find($id) ?? throw new \RuntimeException('Failed to load created contract.');
    }

    public function update(int $id, VendorContractData $data, int $actorId): VendorContract
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE vendor_contracts SET vendor_id = :vid, contract_number = :num, title = :title, start_date = :sdate, end_date = :edate, renewal_date = :rdate, contract_value = :val, currency = :cur, status = :status, attachment_path = :att, notes = :notes, updated_by = :ub, updated_at = :uat WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([
            'vid' => $data->vendorId,
            'num' => $data->contractNumber,
            'title' => $data->title,
            'sdate' => $data->startDate,
            'edate' => $data->endDate,
            'rdate' => $data->renewalDate,
            'val' => $data->contractValue,
            'cur' => $data->currency,
            'status' => $data->status,
            'att' => $data->attachmentPath,
            'notes' => $data->notes,
            'ub' => $actorId,
            'uat' => $now,
            'id' => $id,
        ]);
        return $this->find($id) ?? throw new \RuntimeException('Contract not found.');
    }

    public function delete(int $id, int $actorId): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE vendor_contracts SET deleted_at = ?, updated_by = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$now, $actorId, $now, $id]);
    }

    private function syncExpiringContracts(): void
    {
        $nowDate = date('Y-m-d');
        $thirtyDays = date('Y-m-d', strtotime('+30 days'));

        // Mark expired
        $this->pdo->prepare("UPDATE vendor_contracts SET status = 'expired' WHERE end_date < ? AND status IN ('active', 'expiring') AND deleted_at IS NULL")
            ->execute([$nowDate]);

        // Mark expiring within 30 days
        $this->pdo->prepare("UPDATE vendor_contracts SET status = 'expiring' WHERE end_date >= ? AND end_date <= ? AND status = 'active' AND deleted_at IS NULL")
            ->execute([$nowDate, $thirtyDays]);
    }
}
