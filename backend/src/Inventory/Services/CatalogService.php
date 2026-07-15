<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Services;

use SkyFi\Inventory\Contracts\CatalogRepositoryContract;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class CatalogService
{
    public function __construct(
        private readonly CatalogRepositoryContract $repository,
        private readonly AuditLoggerContract $audit,
    ) {
    }

    public function list(string $resource, bool $activeOnly = false): array
    {
        return $this->repository->list($resource, $activeOnly);
    }

    public function create(string $resource, array $data, int $actorId, ?string $ip = null, ?string $agent = null): array
    {
        $this->validate($resource, $data);
        try {
            $item = $this->repository->create($resource, $data, $actorId);
        } catch (\PDOException $exception) {
            throw new ValidationException([['code' => 'duplicate_or_invalid_reference', 'detail' => 'The catalog value already exists or references an invalid record.']]);
        }
        $this->audit->log($actorId, 'inventory.catalog.created', 'inventory_' . $resource, (int) ($item['id'] ?? 0), null, $item, $ip, $agent);
        return $item;
    }

    public function update(string $resource, int $id, array $data, int $actorId, ?string $ip = null, ?string $agent = null): array
    {
        $old = $this->repository->find($resource, $id) ?? throw new NotFoundException('Inventory catalog record not found.');
        $this->validate($resource, $data);
        try {
            $item = $this->repository->update($resource, $id, $data, $actorId);
        } catch (\PDOException $exception) {
            throw new ValidationException([['code' => 'duplicate_or_invalid_reference', 'detail' => 'The catalog value already exists or references an invalid record.']]);
        }
        $this->audit->log($actorId, 'inventory.catalog.updated', 'inventory_' . $resource, $id, $old, $item, $ip, $agent);
        return $item;
    }

    public function delete(string $resource, int $id, int $actorId, ?string $ip = null, ?string $agent = null): void
    {
        $old = $this->repository->find($resource, $id) ?? throw new NotFoundException('Inventory catalog record not found.');
        try {
            $this->repository->delete($resource, $id, $actorId);
        } catch (\PDOException $exception) {
            throw new ValidationException([['code' => 'catalog_in_use', 'detail' => 'This catalog record is in use and cannot be removed.']]);
        }
        $this->audit->log($actorId, 'inventory.catalog.deleted', 'inventory_' . $resource, $id, $old, null, $ip, $agent);
    }

    public function lookup(string $resource, string $search): array
    {
        return $this->repository->lookup($resource, trim($search));
    }

    private function validate(string $resource, array $data): void
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 200) {
            throw new ValidationException([['code' => 'validation_error', 'detail' => 'Name is required and must not exceed 200 characters.', 'source' => ['pointer' => '/data/attributes/name']]]);
        }
        if ($resource === 'models' && (int) ($data['brand_id'] ?? 0) < 1) {
            throw new ValidationException([['code' => 'validation_error', 'detail' => 'Brand is required.', 'source' => ['pointer' => '/data/attributes/brand_id']]]);
        }
        if ($resource === 'vendors' && isset($data['email']) && $data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException([['code' => 'validation_error', 'detail' => 'Vendor email is invalid.', 'source' => ['pointer' => '/data/attributes/email']]]);
        }
    }
}
