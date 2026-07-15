<?php

declare(strict_types=1);

namespace SkyFi\Backup\Controllers;

use SkyFi\Backup\Repositories\PdoDrPlanRepository;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class DrPlanController
{
    public function __construct(
        private readonly PdoDrPlanRepository $repository
    ) {}

    public function index(Request $request): Response
    {
        $items = $this->repository->list();
        return Response::json(array_map(fn($i) => $i->toArray(), $items));
    }

    public function show(Request $request): Response
    {
        $params = $request->attributes()['route_params'];
        $id = (int) $params['id'];
        $plan = $this->repository->find($id);
        
        if (!$plan) {
            return Response::json(['error' => 'Plan not found'], 404);
        }

        return Response::json($plan->toArray());
    }

    public function update(Request $request): Response
    {
        $params = $request->attributes()['route_params'];
        $id = (int) $params['id'];
        $data = $request->body();

        $plan = $this->repository->update($id, $data);
        return Response::json($plan->toArray());
    }
}
