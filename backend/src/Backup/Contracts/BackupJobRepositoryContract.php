<?php

declare(strict_types=1);

namespace SkyFi\Backup\Contracts;

use SkyFi\Backup\Models\BackupJob;

interface BackupJobRepositoryContract
{
    public function find(int $id): ?BackupJob;
    public function list(array $filters): array;
    public function create(array $data): BackupJob;
    public function update(int $id, array $data): BackupJob;
    public function statistics(): array;
}
