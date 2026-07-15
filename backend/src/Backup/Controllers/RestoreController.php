<?php

declare(strict_types=1);

namespace SkyFi\Backup\Controllers;

use SkyFi\Backup\Services\RestoreService;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class RestoreController
{
    public function __construct(
        private readonly RestoreService $restoreService
    ) {}

    public function history(Request $request): Response
    {
        return Response::json($this->restoreService->getHistory());
    }

    public function execute(Request $request): Response
    {
        $data = $request->body();
        $fileId = (int) ($data['backup_file_id'] ?? 0);
        $env = $data['target_environment'] ?? 'production';

        try {
            $restoreId = $this->restoreService->initiateRestore($fileId, $env);
            return Response::json(['id' => $restoreId, 'message' => 'Restoration started successfully.']);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], 400);
        }
    }
}
