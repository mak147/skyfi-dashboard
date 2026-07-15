<?php

declare(strict_types=1);

namespace SkyFi\Backup\Controllers;

use SkyFi\Backup\Contracts\BackupScheduleRepositoryContract;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class ScheduleController
{
    public function __construct(
        private readonly BackupScheduleRepositoryContract $scheduleRepository
    ) {}

    public function index(Request $request): Response
    {
        $items = $this->scheduleRepository->list();
        return Response::json(array_map(fn($i) => $i->toArray(), $items));
    }

    public function store(Request $request): Response
    {
        $data = $request->body();
        // Validation would go here
        $schedule = $this->scheduleRepository->create($data);
        return Response::json($schedule->toArray(), 201);
    }

    public function update(Request $request): Response
    {
        $params = $request->attributes()['route_params'];
        $id = (int) $params['id'];
        $data = $request->body();

        $schedule = $this->scheduleRepository->update($id, $data);
        return Response::json($schedule->toArray());
    }

    public function destroy(Request $request): Response
    {
        $params = $request->attributes()['route_params'];
        $id = (int) $params['id'];

        $this->scheduleRepository->delete($id);
        return Response::json(null, 204);
    }
}
