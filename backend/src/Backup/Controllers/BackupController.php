<?php

declare(strict_types=1);

namespace SkyFi\Backup\Controllers;

use SkyFi\Backup\Contracts\BackupJobRepositoryContract;
use SkyFi\Backup\Contracts\BackupFileRepositoryContract;
use SkyFi\Backup\Services\BackupService;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class BackupController
{
    public function __construct(
        private readonly BackupJobRepositoryContract $jobRepository,
        private readonly BackupFileRepositoryContract $fileRepository,
        private readonly BackupService $backupService
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'page' => (int) ($request->query()['page'] ?? 1),
            'perPage' => (int) ($request->query()['perPage'] ?? 20),
        ];

        return Response::json($this->jobRepository->list($filters));
    }

    public function statistics(Request $request): Response
    {
        return Response::json($this->jobRepository->statistics());
    }

    public function runManual(Request $request): Response
    {
        $data = $request->body();
        $type = $data['type'] ?? 'full';

        $job = $this->backupService->runBackup($type);

        return Response::json($job->toArray());
    }

    public function files(Request $request): Response
    {
        $filters = [
            'page' => (int) ($request->query()['page'] ?? 1),
            'perPage' => (int) ($request->query()['perPage'] ?? 20),
        ];

        return Response::json($this->fileRepository->list($filters));
    }

    public function verifyFile(Request $request): Response
    {
        $params = $request->attributes()['route_params'];
        $id = (int) $params['id'];

        $file = $this->fileRepository->find($id);
        if (!$file) {
            return Response::json(['error' => 'File not found'], 404);
        }

        // Integrity check
        $status = 'success';
        $details = 'Checksum verified successfully.';

        if (!file_exists($file->filePath) || hash_file('sha256', $file->filePath) !== $file->checksum) {
            $status = 'failure';
            $details = 'File missing or checksum mismatch.';
        }

        $this->fileRepository->addVerification($id, $status, $details);

        return Response::json(['status' => $status, 'details' => $details]);
    }

    public function verificationHistory(Request $request): Response
    {
        $params = $request->attributes()['route_params'];
        $id = (int) $params['id'];

        return Response::json($this->fileRepository->getVerificationHistory($id));
    }
}
