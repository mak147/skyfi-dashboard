<?php

declare(strict_types=1);

namespace SkyFi\Backup\Services;

use SkyFi\Backup\Contracts\BackupJobRepositoryContract;
use SkyFi\Backup\Contracts\BackupFileRepositoryContract;
use SkyFi\Backup\Repositories\PdoStorageProviderRepository;
use SkyFi\Backup\Models\BackupJob;
use SkyFi\Backup\Models\BackupSchedule;
use Exception;

final class BackupService
{
    public function __construct(
        private readonly BackupJobRepositoryContract $jobRepository,
        private readonly BackupFileRepositoryContract $fileRepository,
        private readonly PdoStorageProviderRepository $storageProviderRepository
    ) {}

    public function runBackup(string $type, ?BackupSchedule $schedule = null): BackupJob
    {
        $job = $this->jobRepository->create([
            'schedule_id' => $schedule?->id,
            'type' => $type,
            'status' => 'running',
            'started_at' => date('Y-m-d H:i:s'),
        ]);

        try {
            // Orchestrate backup based on type
            $filePath = $this->performBackup($type);
            $checksum = hash_file('sha256', $filePath);
            $fileSize = filesize($filePath);

            $provider = $this->storageProviderRepository->findDefault();
            if (!$provider) {
                throw new Exception('No default storage provider configured.');
            }

            // In a real app, we'd move the file to the provider's storage
            // For this implementation, we just record the metadata

            $this->fileRepository->create([
                'job_id' => $job->id,
                'storage_provider_id' => $provider->id,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'checksum' => $checksum,
                'metadata' => ['type' => $type, 'source' => 'SkyFi Orchestrator'],
                'expires_at' => $schedule ? date('Y-m-d H:i:s', strtotime("+{$schedule->retentionDays} days")) : null,
            ]);

            return $this->jobRepository->update($job->id, [
                'status' => 'completed',
                'finished_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Exception $e) {
            return $this->jobRepository->update($job->id, [
                'status' => 'failed',
                'finished_at' => date('Y-m-d H:i:s'),
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    private function performBackup(string $type): string
    {
        $tmpPath = '/tmp/skyfi_backup_' . $type . '_' . time() . '.zip';
        
        // Placeholder for actual backup logic (DB dump, file tar, etc.)
        // Create a dummy file for demonstration
        file_put_contents($tmpPath, "SkyFi Backup Content for $type");
        
        return $tmpPath;
    }
}
