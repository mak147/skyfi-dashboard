<?php

declare(strict_types=1);

namespace SkyFi\Audit\Repositories;

use PDO;
use SkyFi\Audit\Contracts\ComplianceRepositoryContract;
use SkyFi\Audit\DomainModels\CompliancePolicy;
use SkyFi\Audit\DTOs\CompliancePolicyData;

final class PdoComplianceRepository implements ComplianceRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM compliance_policies ORDER BY created_at DESC');
        return array_map(
            static fn(array $row): CompliancePolicy => CompliancePolicy::fromRow($row),
            $stmt->fetchAll() ?: [],
        );
    }

    public function find(int $id): ?CompliancePolicy
    {
        $stmt = $this->pdo->prepare('SELECT * FROM compliance_policies WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return null;
        }
        return CompliancePolicy::fromRow($row);
    }

    public function create(CompliancePolicyData $data): CompliancePolicy
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO compliance_policies (name, description, policy_type, rules, is_active, created_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data->name,
            $data->description,
            $data->policyType,
            json_encode($data->rules, JSON_THROW_ON_ERROR),
            $data->isActive,
            $data->createdBy,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        return $this->find($id) ?? CompliancePolicy::fromRow(['id' => $id]);
    }

    public function update(int $id, CompliancePolicyData $data): ?CompliancePolicy
    {
        $stmt = $this->pdo->prepare(
            'UPDATE compliance_policies SET name = ?, description = ?, policy_type = ?, rules = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([
            $data->name,
            $data->description,
            $data->policyType,
            json_encode($data->rules, JSON_THROW_ON_ERROR),
            $data->isActive,
            $id,
        ]);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE compliance_policies SET is_active = 0 WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
