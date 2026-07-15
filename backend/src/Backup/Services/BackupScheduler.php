<?php

declare(strict_types=1);

namespace SkyFi\Backup\Services;

use SkyFi\Backup\Contracts\BackupScheduleRepositoryContract;
use SkyFi\Backup\Services\BackupService;
use SkyFi\Backup\Contracts\BackupFileRepositoryContract;

final class BackupScheduler
{
    public function __construct(
        private readonly BackupScheduleRepositoryContract $scheduleRepository,
        private readonly BackupService $backupService,
        private readonly BackupFileRepositoryContract $fileRepository
    ) {}

    public function run(): void
    {
        // 1. Find due schedules
        $dueSchedules = $this->scheduleRepository->findDue();

        foreach ($dueSchedules as $schedule) {
            // 2. Run backup
            $this->backupService->runBackup($schedule->type, $schedule);

            // 3. Update next run time (simplified)
            $this->scheduleRepository->update($schedule->id, [
                'last_run_at' => date('Y-m-d H:i:s'),
                'next_run_at' => date('Y-m-d H:i:s', strtotime('+1 day')), // Simplified cron handling
            ]);
        }

        // 4. Cleanup expired backups
        $this->fileRepository->deleteExpired();
    }
}
