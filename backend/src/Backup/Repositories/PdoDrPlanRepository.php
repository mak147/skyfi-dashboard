<?php

declare(strict_types=1);

namespace SkyFi\Backup\Repositories;

use PDO;
use SkyFi\Backup\Models\DrPlan;

final class PdoDrPlanRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function find(int $id): ?DrPlan
    {
        $stmt = $this->pdo->prepare('SELECT * FROM dr_plans WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? DrPlan::fromRow($row) : null;
    }

    public function list(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM dr_plans ORDER BY name ASC');
        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = DrPlan::fromRow($row);
        }
        return $items;
    }

    public function update(int $id, array $data): DrPlan
    {
        $sets = array_map(fn($c) => "$c = :$c", array_keys($data));
        $sql = sprintf('UPDATE dr_plans SET %s WHERE id = :id', implode(',', $sets));
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($data, ['id' => $id]));
        
        return $this->find($id);
    }
}
