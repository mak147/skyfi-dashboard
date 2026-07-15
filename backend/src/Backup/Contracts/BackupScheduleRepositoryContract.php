<?php

declare(strict_types=1);

namespace SkyFi\Backup\Contracts;

use SkyFi\Backup\Models\BackupSchedule;

interface BackupScheduleRepositoryContract
{
    public function find(int $id): ?BackupSchedule;
    public function list(): array;
    public function create(array $data): BackupSchedule;
    public function update(int $id, array $data): BackupSchedule;
    public function delete(int $id): void;
    public function findDue(): array;
}
