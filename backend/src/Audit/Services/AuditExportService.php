<?php

declare(strict_types=1);

namespace SkyFi\Audit\Services;

use SkyFi\Audit\Contracts\AuditExportRepositoryContract;
use SkyFi\Audit\Contracts\AuditLogRepositoryContract;
use SkyFi\Audit\DTOs\AuditLogFilters;
use SkyFi\Audit\DTOs\ExportRequestData;

final class AuditExportService
{
    public function __construct(
        private readonly AuditLogRepositoryContract $auditLogs,
        private readonly AuditExportRepositoryContract $exports,
        private readonly string $exportDir,
    ) {}

    /** @return array<string, mixed> */
    public function createExport(int $userId, ExportRequestData $data): array
    {
        $export = $this->exports->create($userId, $data->format, $data->toFilterArray());

        try {
            $this->exports->updateStatus($export->id(), 'processing');

            $filters = new AuditLogFilters(
                module: $data->module,
                action: $data->action,
                entityType: $data->entityType,
                entityId: $data->entityId,
                userId: $data->userId,
                severity: $data->severity,
                dateFrom: $data->dateFrom,
                dateTo: $data->dateTo,
                search: $data->search,
                page: 1,
                perPage: 10000,
            );

            $result = $this->auditLogs->search($filters);

            $filename = sprintf('audit_export_%d_%s.%s', $export->id(), date('YmdHis'), $data->format);
            $filePath = rtrim($this->exportDir, '/') . '/' . $filename;

            if ($data->format === 'csv') {
                $this->writeCsv($result['items'], $filePath);
            } else {
                $this->writeJson($result['items'], $filePath);
            }

            $this->exports->updateStatus($export->id(), 'completed', $filename, null, count($result['items']));
        } catch (\Throwable $e) {
            $this->exports->updateStatus($export->id(), 'failed', null, $e->getMessage());
        }

        $updated = $this->exports->find($export->id());
        return $updated ? $updated->toArray() : $export->toArray();
    }

    /** @param list<array<string, mixed>> $items */
    private function writeCsv(array $items, string $filePath): void
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $stream = fopen($filePath, 'w');
        if ($stream === false) {
            throw new \RuntimeException('Unable to create export file.');
        }

        if ($items !== []) {
            $headers = array_keys($items[0]);
            fputcsv($stream, $headers);
            foreach ($items as $item) {
                $row = [];
                foreach ($headers as $header) {
                    $val = $item[$header] ?? null;
                    $row[] = is_array($val) ? json_encode($val, JSON_THROW_ON_ERROR) : $val;
                }
                fputcsv($stream, $row);
            }
        }

        fclose($stream);
    }

    /** @param list<array<string, mixed>> $items */
    private function writeJson(array $items, string $filePath): void
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($filePath, json_encode($items, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    }
}
