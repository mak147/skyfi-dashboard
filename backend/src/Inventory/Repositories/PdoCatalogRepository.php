<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Repositories;

use PDO;
use SkyFi\Inventory\Contracts\CatalogRepositoryContract;
use SkyFi\Shared\Exceptions\ValidationException;

final class PdoCatalogRepository implements CatalogRepositoryContract
{
    /** @var array<string, array{table: string, columns: array<int, string>, actor: bool, soft: bool}> */
    private const RESOURCES = [
        'categories' => ['table' => 'inventory_categories', 'columns' => ['parent_id', 'code', 'name', 'description', 'status'], 'actor' => false, 'soft' => true],
        'brands' => ['table' => 'inventory_brands', 'columns' => ['code', 'name', 'website', 'notes', 'status'], 'actor' => false, 'soft' => true],
        'models' => ['table' => 'inventory_product_models', 'columns' => ['brand_id', 'name', 'model_number', 'description', 'specifications', 'status'], 'actor' => false, 'soft' => true],
        'units' => ['table' => 'inventory_units', 'columns' => ['code', 'name', 'symbol', 'decimal_places', 'status'], 'actor' => false, 'soft' => false],
        'vendors' => ['table' => 'vendors', 'columns' => ['code', 'name', 'status', 'contact_name', 'email', 'phone', 'website', 'tax_id', 'payment_terms', 'notes'], 'actor' => true, 'soft' => true],
    ];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(string $resource, bool $activeOnly = false): array
    {
        $config = $this->config($resource);
        $where = [];
        if ($config['soft']) {
            $where[] = 'x.deleted_at IS NULL';
        }
        if ($activeOnly) {
            $where[] = "x.status = 'active'";
        }
        $select = 'x.*';
        $join = '';
        if ($resource === 'categories') {
            $select .= ', p.name AS parent_name';
            $join = ' LEFT JOIN inventory_categories p ON p.id = x.parent_id';
        } elseif ($resource === 'models') {
            $select .= ', b.name AS brand_name';
            $join = ' JOIN inventory_brands b ON b.id = x.brand_id';
        }
        $sql = "SELECT {$select} FROM {$config['table']} x{$join}" . ($where !== [] ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY x.name ASC';
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(string $resource, int $id): ?array
    {
        $config = $this->config($resource);
        $sql = "SELECT * FROM {$config['table']} WHERE id = ?" . ($config['soft'] ? ' AND deleted_at IS NULL' : '');
        $statement = $this->pdo->prepare($sql);
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(string $resource, array $data, int $actorId): array
    {
        $config = $this->config($resource);
        $values = $this->normalize($resource, $data, $config['columns']);
        if ($config['actor']) {
            $values['created_by'] = $actorId;
        }
        $columns = array_keys($values);
        $sql = "INSERT INTO {$config['table']} (" . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $this->pdo->prepare($sql)->execute(array_values($values));
        return $this->find($resource, (int) $this->pdo->lastInsertId()) ?? [];
    }

    public function update(string $resource, int $id, array $data, int $actorId): array
    {
        $config = $this->config($resource);
        $values = $this->normalize($resource, $data, $config['columns']);
        if ($config['actor']) {
            $values['updated_by'] = $actorId;
        }
        $sets = array_map(static fn(string $column): string => $column . ' = ?', array_keys($values));
        $this->pdo->prepare("UPDATE {$config['table']} SET " . implode(', ', $sets) . ' WHERE id = ?')->execute([...array_values($values), $id]);
        return $this->find($resource, $id) ?? [];
    }

    public function delete(string $resource, int $id, int $actorId): void
    {
        $config = $this->config($resource);
        if ($config['soft']) {
            $actorSql = $config['actor'] ? ', updated_by = ?' : '';
            $parameters = $config['actor'] ? [$actorId, $id] : [$id];
            $this->pdo->prepare("UPDATE {$config['table']} SET status = 'inactive', deleted_at = CURRENT_TIMESTAMP{$actorSql} WHERE id = ?")->execute($parameters);
            return;
        }
        $this->pdo->prepare("UPDATE {$config['table']} SET status = 'inactive' WHERE id = ?")->execute([$id]);
    }

    public function lookup(string $resource, string $search): array
    {
        if ($resource === 'customers') {
            return $this->simpleLookup('customers', "CONCAT(customer_code, ' - ', full_name)", $search, 'deleted_at IS NULL');
        }
        if ($resource === 'towers') {
            return $this->simpleLookup('towers', "CONCAT(COALESCE(code, ''), ' - ', name)", $search, 'deleted_at IS NULL');
        }
        if ($resource === 'pop-sites') {
            return $this->simpleLookup('pop_sites', "CONCAT(code, ' - ', name)", $search, 'deleted_at IS NULL');
        }
        if ($resource === 'technicians' || $resource === 'users') {
            return $this->simpleLookup('users', "CONCAT(name, ' - ', email)", $search, 'deleted_at IS NULL');
        }
        if ($resource === 'network-devices') {
            return $this->simpleLookup('network_devices', "CONCAT(name, ' - ', COALESCE(serial_number, ''))", $search, 'deleted_at IS NULL');
        }
        if ($resource === 'support-tickets') {
            return $this->simpleLookup('support_tickets', "CONCAT(ticket_number, ' - ', subject)", $search, 'deleted_at IS NULL');
        }
        if (isset(self::RESOURCES[$resource])) {
            return array_slice(array_values(array_filter($this->list($resource, true), static function (array $row) use ($search): bool {
                return $search === '' || str_contains(strtolower((string) ($row['name'] ?? $row['code'] ?? '')), strtolower($search));
            })), 0, 30);
        }
        if ($resource === 'products') {
            $statement = $this->pdo->prepare("SELECT id, CONCAT(sku, ' - ', name) AS label, sku, name, tracking_mode FROM inventory_products WHERE deleted_at IS NULL AND status = 'active' AND (? = '' OR sku LIKE ? OR name LIKE ? OR barcode LIKE ?) ORDER BY name LIMIT 30");
            $like = '%' . $search . '%';
            $statement->execute([$search, $like, $like, $like]);
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }
        if ($resource === 'assets') {
            $statement = $this->pdo->prepare("SELECT a.id, CONCAT(a.asset_tag, ' - ', p.name) AS label, a.asset_tag, a.serial_number, a.mac_address, a.status FROM inventory_assets a JOIN inventory_products p ON p.id = a.product_id WHERE a.deleted_at IS NULL AND (? = '' OR a.asset_tag LIKE ? OR a.serial_number LIKE ? OR a.mac_address LIKE ? OR a.barcode LIKE ?) ORDER BY a.asset_tag LIMIT 30");
            $like = '%' . $search . '%';
            $statement->execute([$search, $like, $like, $like, $like]);
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }
        if ($resource === 'warehouses') {
            return $this->simpleLookup('warehouses', "CONCAT(code, ' - ', name)", $search, "deleted_at IS NULL AND status = 'active'");
        }
        if ($resource === 'warehouse-locations') {
            $statement = $this->pdo->prepare("SELECT l.id, CONCAT(w.code, ' / ', l.code, ' - ', l.name) AS label, l.warehouse_id FROM warehouse_locations l JOIN warehouses w ON w.id = l.warehouse_id WHERE l.deleted_at IS NULL AND l.status = 'active' AND (? = '' OR l.code LIKE ? OR l.name LIKE ? OR w.name LIKE ?) ORDER BY w.name, l.name LIMIT 50");
            $like = '%' . $search . '%';
            $statement->execute([$search, $like, $like, $like]);
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    /** @param array<int, string> $allowed @return array<string, mixed> */
    private function normalize(string $resource, array $data, array $allowed): array
    {
        $values = [];
        foreach ($allowed as $column) {
            if (array_key_exists($column, $data)) {
                $value = $data[$column];
                if ($column === 'specifications' && is_array($value)) {
                    $value = json_encode($value, JSON_THROW_ON_ERROR);
                }
                $values[$column] = is_string($value) ? trim($value) : $value;
            }
        }
        foreach (['code', 'name'] as $required) {
            if (in_array($required, $allowed, true) && trim((string) ($values[$required] ?? '')) === '') {
                throw new ValidationException([['code' => 'validation_error', 'detail' => ucfirst($required) . ' is required.', 'source' => ['pointer' => '/data/attributes/' . $required]]]);
            }
        }
        if (isset($values['code'])) {
            $values['code'] = strtoupper((string) $values['code']);
        }
        if ($resource === 'models' && (int) ($values['brand_id'] ?? 0) < 1) {
            throw new ValidationException([['code' => 'validation_error', 'detail' => 'Brand is required.', 'source' => ['pointer' => '/data/attributes/brand_id']]]);
        }
        if ($resource === 'units') {
            $values['symbol'] = trim((string) ($values['symbol'] ?? '')) ?: (string) ($values['code'] ?? '');
            $values['decimal_places'] = min(4, max(0, (int) ($values['decimal_places'] ?? 0)));
        }
        return $values;
    }

    /** @return array<int, array<string, mixed>> */
    private function simpleLookup(string $table, string $label, string $search, string $where): array
    {
        $statement = $this->pdo->prepare("SELECT id, {$label} AS label FROM {$table} WHERE {$where} AND (? = '' OR {$label} LIKE ?) ORDER BY label LIMIT 30");
        $statement->execute([$search, '%' . $search . '%']);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array{table: string, columns: array<int, string>, actor: bool, soft: bool} */
    private function config(string $resource): array
    {
        if (!isset(self::RESOURCES[$resource])) {
            throw new ValidationException([['code' => 'invalid_resource', 'detail' => 'Unsupported inventory catalog resource.']]);
        }
        return self::RESOURCES[$resource];
    }
}
