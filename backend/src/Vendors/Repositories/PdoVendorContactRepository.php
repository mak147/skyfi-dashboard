<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Repositories;

use PDO;
use SkyFi\Vendors\Contracts\VendorContactRepositoryContract;
use SkyFi\Vendors\DomainModels\VendorContact;
use SkyFi\Vendors\DTOs\VendorContactData;

final class PdoVendorContactRepository implements VendorContactRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listByVendor(?int $vendorId = null): array
    {
        if ($vendorId !== null && $vendorId > 0) {
            $stmt = $this->pdo->prepare(
                'SELECT vc.*, v.name AS vendor_name
                 FROM vendor_contacts vc
                 JOIN vendors v ON v.id = vc.vendor_id
                 WHERE vc.vendor_id = ? AND vc.deleted_at IS NULL
                 ORDER BY vc.is_primary DESC, vc.first_name ASC'
            );
            $stmt->execute([$vendorId]);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT vc.*, v.name AS vendor_name
                 FROM vendor_contacts vc
                 JOIN vendors v ON v.id = vc.vendor_id
                 WHERE vc.deleted_at IS NULL
                 ORDER BY v.name ASC, vc.is_primary DESC, vc.first_name ASC'
            );
            $stmt->execute();
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(static fn(array $row) => VendorContact::fromRow($row), $rows);
    }

    public function find(int $id): ?VendorContact
    {
        $stmt = $this->pdo->prepare(
            'SELECT vc.*, v.name AS vendor_name
             FROM vendor_contacts vc
             JOIN vendors v ON v.id = vc.vendor_id
             WHERE vc.id = ? AND vc.deleted_at IS NULL'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? VendorContact::fromRow($row) : null;
    }

    public function create(VendorContactData $data, int $actorId): VendorContact
    {
        $now = date('Y-m-d H:i:s');
        if ($data->isPrimary) {
            $this->resetPrimaryContact($data->vendorId);
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO vendor_contacts (vendor_id, first_name, last_name, email, phone, department, position, is_primary, is_emergency, notes, created_by, updated_by, created_at, updated_at)
             VALUES (:vid, :fn, :ln, :email, :phone, :dept, :pos, :primary, :emerg, :notes, :cb, :ub, :cat, :uat)'
        );
        $stmt->execute([
            'vid' => $data->vendorId,
            'fn' => $data->firstName,
            'ln' => $data->lastName,
            'email' => $data->email,
            'phone' => $data->phone,
            'dept' => $data->department,
            'pos' => $data->position,
            'primary' => $data->isPrimary ? 1 : 0,
            'emerg' => $data->isEmergency ? 1 : 0,
            'notes' => $data->notes,
            'cb' => $actorId,
            'ub' => $actorId,
            'cat' => $now,
            'uat' => $now,
        ]);
        $id = (int) $this->pdo->lastInsertId();
        return $this->find($id) ?? throw new \RuntimeException('Failed to load created contact.');
    }

    public function update(int $id, VendorContactData $data, int $actorId): VendorContact
    {
        $now = date('Y-m-d H:i:s');
        if ($data->isPrimary) {
            $this->resetPrimaryContact($data->vendorId, $id);
        }
        $stmt = $this->pdo->prepare(
            'UPDATE vendor_contacts SET vendor_id = :vid, first_name = :fn, last_name = :ln, email = :email, phone = :phone, department = :dept, position = :pos, is_primary = :primary, is_emergency = :emerg, notes = :notes, updated_by = :ub, updated_at = :uat WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([
            'vid' => $data->vendorId,
            'fn' => $data->firstName,
            'ln' => $data->lastName,
            'email' => $data->email,
            'phone' => $data->phone,
            'dept' => $data->department,
            'pos' => $data->position,
            'primary' => $data->isPrimary ? 1 : 0,
            'emerg' => $data->isEmergency ? 1 : 0,
            'notes' => $data->notes,
            'ub' => $actorId,
            'uat' => $now,
            'id' => $id,
        ]);
        return $this->find($id) ?? throw new \RuntimeException('Contact not found.');
    }

    public function delete(int $id, int $actorId): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE vendor_contacts SET deleted_at = ?, updated_by = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$now, $actorId, $now, $id]);
    }

    private function resetPrimaryContact(int $vendorId, ?int $exceptId = null): void
    {
        if ($exceptId !== null) {
            $stmt = $this->pdo->prepare('UPDATE vendor_contacts SET is_primary = 0 WHERE vendor_id = ? AND id != ?');
            $stmt->execute([$vendorId, $exceptId]);
        } else {
            $stmt = $this->pdo->prepare('UPDATE vendor_contacts SET is_primary = 0 WHERE vendor_id = ?');
            $stmt->execute([$vendorId]);
        }
    }
}
