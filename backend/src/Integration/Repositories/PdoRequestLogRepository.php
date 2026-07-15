<?php

declare(strict_types=1);

namespace SkyFi\Integration\Repositories;

use PDO;
use SkyFi\Integration\Contracts\RequestLogRepositoryContract;
use SkyFi\Integration\DomainModels\ApiRequestLog;
use SkyFi\Integration\DTOs\RequestLogFilters;

final class PdoRequestLogRepository implements RequestLogRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function list(RequestLogFilters $filters): array
    {
        $where = ['1=1'];
        $params = [];

        if ($filters->apiKeyId !== null) {
            $where[] = 'api_key_id = :api_key_id';
            $params['api_key_id'] = $filters->apiKeyId;
        }
        if ($filters->clientApplicationId !== null) {
            $where[] = 'client_application_id = :client_application_id';
            $params['client_application_id'] = $filters->clientApplicationId;
        }
        if ($filters->method !== null) {
            $where[] = 'method = :method';
            $params['method'] = $filters->method;
        }
        if ($filters->path !== null) {
            $where[] = 'path LIKE :path';
            $params['path'] = '%' . $filters->path . '%';
        }
        if ($filters->statusCode !== null) {
            $where[] = 'status_code = :status_code';
            $params['status_code'] = $filters->statusCode;
        }

        $whereSql = implode(' AND ', $where);
        $count = $this->pdo->prepare("SELECT COUNT(*) FROM api_request_logs WHERE {$whereSql}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $offset = ($filters->page - 1) * $filters->perPage;

        $stmt = $this->pdo->prepare(
            "SELECT * FROM api_request_logs WHERE {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $filters->perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = array_map(
            static fn(array $row): ApiRequestLog => ApiRequestLog::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []
        );

        return [
            'items' => $items,
            'page' => $filters->page,
            'perPage' => $filters->perPage,
            'total' => $total,
            'lastPage' => (int) max(1, (int) ceil($total / $filters->perPage)),
        ];
    }

    public function create(array $data): ApiRequestLog
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn(string $c): string => ':' . $c, $columns);
        $stmt = $this->pdo->prepare(
            'INSERT INTO api_request_logs (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')'
        );
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $stmt->bindValue($key, json_encode($value, JSON_THROW_ON_ERROR));
            } elseif (is_bool($value)) {
                $stmt->bindValue($key, (int) $value, PDO::PARAM_INT);
            } elseif ($value === null) {
                $stmt->bindValue($key, null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();
        $id = (int) $this->pdo->lastInsertId();
        $fetch = $this->pdo->prepare('SELECT * FROM api_request_logs WHERE id = :id');
        $fetch->execute(['id' => $id]);

        return ApiRequestLog::fromRow($fetch->fetch(PDO::FETCH_ASSOC) ?: ['id' => $id] + $data);
    }

    public function aggregateStats(?string $from = null, ?string $to = null): array
    {
        $where = '1=1';
        $params = [];
        if ($from !== null) {
            $where .= ' AND created_at >= :from';
            $params['from'] = $from;
        }
        if ($to !== null) {
            $where .= ' AND created_at <= :to';
            $params['to'] = $to;
        }

        $sql = "SELECT
            COUNT(*) as total_requests,
            COUNT(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 END) as success_count,
            COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_count,
            AVG(duration_ms) as avg_duration_ms,
            COUNT(DISTINCT api_key_id) as unique_api_keys,
            COUNT(DISTINCT path) as unique_endpoints
        FROM api_request_logs WHERE {$where}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_requests' => (int) ($row['total_requests'] ?? 0),
            'success_count' => (int) ($row['success_count'] ?? 0),
            'error_count' => (int) ($row['error_count'] ?? 0),
            'avg_duration_ms' => $row['avg_duration_ms'] !== null ? (float) $row['avg_duration_ms'] : null,
            'unique_api_keys' => (int) ($row['unique_api_keys'] ?? 0),
            'unique_endpoints' => (int) ($row['unique_endpoints'] ?? 0),
        ];
    }
}
