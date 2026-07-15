<?php

declare(strict_types=1);

namespace SkyFi\Backup\Controllers;

use SkyFi\Backup\Repositories\PdoStorageProviderRepository;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class StorageProviderController
{
    public function __construct(
        private readonly PdoStorageProviderRepository $repository
    ) {}

    public function index(Request $request): Response
    {
        $items = $this->repository->list();
        return Response::json(array_map(fn($i) => $i->toArray(), $items));
    }

    public function store(Request $request): Response
    {
        $data = $request->body();
        $provider = $this->repository->create($data);
        return Response::json($provider->toArray(), 201);
    }

    public function update(Request $request): Response
    {
        $params = $request->attributes()['route_params'];
        $id = (int) $params['id'];
        $data = $request->body();

        $provider = $this->repository->update($id, $data);
        return Response::json($provider->toArray());
    }
}
