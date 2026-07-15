<?php

declare(strict_types=1);

namespace SkyFi\Audit\Contracts;

use SkyFi\Audit\DomainModels\CompliancePolicy;
use SkyFi\Audit\DTOs\CompliancePolicyData;

interface ComplianceRepositoryContract
{
    /** @return list<CompliancePolicy> */
    public function findAll(): array;

    public function find(int $id): ?CompliancePolicy;

    public function create(CompliancePolicyData $data): CompliancePolicy;

    public function update(int $id, CompliancePolicyData $data): ?CompliancePolicy;

    public function delete(int $id): bool;
}
