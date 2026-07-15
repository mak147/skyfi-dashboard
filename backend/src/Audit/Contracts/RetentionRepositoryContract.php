<?php

declare(strict_types=1);

namespace SkyFi\Audit\Contracts;

use SkyFi\Audit\DomainModels\RetentionPolicy;
use SkyFi\Audit\DTOs\RetentionPolicyData;

interface RetentionRepositoryContract
{
    /** @return list<RetentionPolicy> */
    public function findAll(): array;

    public function find(int $id): ?RetentionPolicy;

    public function create(RetentionPolicyData $data): RetentionPolicy;

    public function update(int $id, RetentionPolicyData $data): ?RetentionPolicy;

    public function delete(int $id): bool;
}
