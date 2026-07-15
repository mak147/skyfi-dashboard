<?php

declare(strict_types=1);

namespace SkyFi\Backup\Contracts;

use SkyFi\Backup\Models\BackupFile;

interface BackupFileRepositoryContract
{
    public function find(int $id): ?BackupFile;
    public function list(array $filters): array;
    public function create(array $data): BackupFile;
    public function update(int $id, array $data): BackupFile;
    public function delete(int $id): void;
    public function deleteExpired(): int;
    public function addVerification(int $fileId, string $status, ?string $details): void;
    public function getVerificationHistory(int $fileId): array;
}
