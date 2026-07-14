<?php

declare(strict_types=1);

namespace SkyFi\Pppoe\Contracts;

use SkyFi\Pppoe\DomainModels\PppoeAccount;
use SkyFi\Pppoe\DTOs\CreatePppoeAccountData;
use SkyFi\Pppoe\DTOs\PppoeListFilters;
use SkyFi\Pppoe\DTOs\UpdatePppoeAccountData;

interface PppoeServiceContract
{
    /** @return array{items: array<int, PppoeAccount>, total: int, page: int, perPage: int, lastPage: int} */
    public function list(PppoeListFilters $filters): array;

    public function get(int $id): PppoeAccount;

    public function create(CreatePppoeAccountData $data, int $actorId, ?string $ip, ?string $userAgent): PppoeAccount;

    public function update(int $id, UpdatePppoeAccountData $data, int $actorId, ?string $ip, ?string $userAgent): PppoeAccount;

    public function delete(int $id, int $actorId, ?string $ip, ?string $userAgent): void;

    public function setEnabled(int $id, bool $isEnabled, int $actorId, ?string $ip, ?string $userAgent): PppoeAccount;

    public function suspend(int $id, int $actorId, ?string $ip, ?string $userAgent): PppoeAccount;

    public function resume(int $id, int $actorId, ?string $ip, ?string $userAgent): PppoeAccount;

    public function reconnect(int $id, int $actorId, ?string $ip, ?string $userAgent): void;

    public function resetPassword(int $id, string $newPassword, int $actorId, ?string $ip, ?string $userAgent): PppoeAccount;

    public function changePackage(int $id, int $newPackageId, ?string $newProfile, int $actorId, ?string $ip, ?string $userAgent): PppoeAccount;
}
