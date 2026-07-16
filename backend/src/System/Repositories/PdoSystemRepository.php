<?php declare(strict_types=1);
namespace SkyFi\System\Repositories;

use PDO;
use SkyFi\Shared\Exceptions\NotFoundException;

class PdoSystemRepository
{
    public function __construct(protected readonly PDO $pdo) {}

    /** @param array<int,string> $json */
    protected function singleton(string $table, array $defaults, array $json = []): array
    {
        // Safe because table names are hardcoded in the concrete classes
        $row = $this->pdo->query("SELECT * FROM {$table} ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            $columns = array_keys($defaults);
            $placeholders = array_map(static fn(string $c): string => ':' . $c, $columns);
            $stmt = $this->pdo->prepare("INSERT INTO {$table} (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")");
            foreach ($defaults as $key => $value) $stmt->bindValue($key, in_array($key, $json, true) ? json_encode($value, JSON_THROW_ON_ERROR) : $value);
            $stmt->execute();
            $row = $this->pdo->query("SELECT * FROM {$table} ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
        }
        return $this->decode($row, $json);
    }

    protected function updateSingleton(string $table, array $data, array $defaults, array $allowed, array $json = [], ?int $userId = null): array
    {
        $current = $this->singleton($table, $defaults, $json);
        $clean = [];
        foreach ($allowed as $field) if (array_key_exists($field, $data)) $clean[$field] = $data[$field];
        if ($userId !== null) $clean['updated_by'] = $userId;
        if ($clean !== []) {
            $sets = array_map(static fn(string $c): string => "{$c} = :{$c}", array_keys($clean));
            $stmt = $this->pdo->prepare("UPDATE {$table} SET " . implode(',', $sets) . " WHERE id = :id");
            foreach ($clean as $key=>$value) $stmt->bindValue($key, in_array($key,$json,true) ? json_encode($value, JSON_THROW_ON_ERROR) : $value);
            $stmt->bindValue('id', (int)$current['id'], PDO::PARAM_INT);
            $stmt->execute();
        }
        return $this->singleton($table, $defaults, $json);
    }

    protected function findOne(string $table, int $id): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$table} WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute(['id'=>$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) throw new NotFoundException('The requested system record was not found.');
        return $row;
    }

    protected function listRows(string $table, array $query, array $searchColumns, array $filters = []): array
    {
        $where = ['deleted_at IS NULL']; $params = [];
        if (($query['search'] ?? '') !== '') { 
            $likes=[]; 
            foreach ($searchColumns as $c) $likes[]="{$c} LIKE :search"; 
            $where[]='(' . implode(' OR ', $likes) . ')'; 
            $params['search']='%' . (string)$query['search'] . '%'; 
        }
        foreach ($filters as $field) {
            if (($query[$field] ?? '') !== '') { 
                $where[]="{$field} = :{$field}"; 
                $params[$field]=$query[$field]; 
            }
        }
        
        $page = max(1, (int)($query['page']['number'] ?? $query['page'] ?? 1));
        $perPage = max(1, min(100, (int)($query['page']['size'] ?? $query['per_page'] ?? 25)));
        $whereSql = 'WHERE ' . implode(' AND ', $where);
        
        $count = $this->pdo->prepare("SELECT COUNT(*) FROM {$table} {$whereSql}"); 
        $count->execute($params); 
        $total=(int)$count->fetchColumn();
        
        $offset = ($page - 1) * $perPage;
        
        // BUG-001: Strict parameter binding for Limit and Offset.
        // PHP 8 doesn't perfectly sanitize strings injected into PDO queries, so we must bind integer values tightly.
        $stmt = $this->pdo->prepare("SELECT * FROM {$table} {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        foreach ($params as $key=>$value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT); 
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT); 
        $stmt->execute();
        
        return [
            'items' => $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int)max(1,ceil($total/$perPage))
            ]
        ];
    }

    protected function insertRow(string $table, array $data, ?int $userId = null): array
    {
        if ($userId !== null) { $data['created_by']=$userId; $data['updated_by']=$userId; }
        $cols=array_keys($data); $ph=array_map(static fn(string $c): string=>':' . $c, $cols);
        $stmt=$this->pdo->prepare("INSERT INTO {$table} (".implode(',',$cols).") VALUES (".implode(',',$ph).")"); $stmt->execute($data);
        return $this->findOne($table,(int)$this->pdo->lastInsertId());
    }

    protected function updateRow(string $table, int $id, array $data, ?int $userId = null): array
    {
        $this->findOne($table,$id); if ($userId !== null) $data['updated_by']=$userId;
        if ($data !== []) { $sets=array_map(static fn(string $c): string=>"{$c} = :{$c}", array_keys($data)); $stmt=$this->pdo->prepare("UPDATE {$table} SET ".implode(',',$sets)." WHERE id = :id AND deleted_at IS NULL"); $stmt->execute(array_merge($data,['id'=>$id])); }
        return $this->findOne($table,$id);
    }

    protected function softDeleteRow(string $table, int $id): void { $this->findOne($table,$id); $stmt=$this->pdo->prepare("UPDATE {$table} SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id"); $stmt->execute(['id'=>$id]); }
    protected function statusRow(string $table, int $id, string $status, ?int $userId = null): array { return $this->updateRow($table,$id,['status'=>$status],$userId); }
    protected function decode(array $row, array $json): array { foreach ($json as $field) if (isset($row[$field]) && is_string($row[$field])) $row[$field]=json_decode($row[$field], true) ?: []; return $row; }
}
