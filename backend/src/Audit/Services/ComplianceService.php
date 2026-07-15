<?php

declare(strict_types=1);

namespace SkyFi\Audit\Services;

use SkyFi\Audit\Contracts\ComplianceRepositoryContract;
use SkyFi\Audit\Contracts\ComplianceServiceContract;
use SkyFi\Audit\Contracts\RetentionRepositoryContract;
use SkyFi\Audit\DTOs\CompliancePolicyData;
use SkyFi\Audit\DTOs\RetentionPolicyData;
use SkyFi\Shared\Exceptions\NotFoundException;

final class ComplianceService implements ComplianceServiceContract
{
    public function __construct(
        private readonly ComplianceRepositoryContract $policies,
        private readonly RetentionRepositoryContract $retention,
    ) {}

    public function listPolicies(): array
    {
        return array_map(static fn($p) => $p->toArray(), $this->policies->findAll());
    }

    public function getPolicy(int $id): array
    {
        $policy = $this->policies->find($id);
        if ($policy === null) {
            throw new NotFoundException('Compliance policy not found.');
        }
        return $policy->toArray();
    }

    public function createPolicy(CompliancePolicyData $data): array
    {
        $policy = $this->policies->create($data);
        return $policy->toArray();
    }

    public function updatePolicy(int $id, CompliancePolicyData $data): array
    {
        $policy = $this->policies->update($id, $data);
        if ($policy === null) {
            throw new NotFoundException('Compliance policy not found.');
        }
        return $policy->toArray();
    }

    public function deletePolicy(int $id): void
    {
        if (!$this->policies->delete($id)) {
            throw new NotFoundException('Compliance policy not found.');
        }
    }

    public function listRetentionPolicies(): array
    {
        return array_map(static fn($p) => $p->toArray(), $this->retention->findAll());
    }

    public function getRetentionPolicy(int $id): array
    {
        $policy = $this->retention->find($id);
        if ($policy === null) {
            throw new NotFoundException('Retention policy not found.');
        }
        return $policy->toArray();
    }

    public function createRetentionPolicy(RetentionPolicyData $data): array
    {
        $policy = $this->retention->create($data);
        return $policy->toArray();
    }

    public function updateRetentionPolicy(int $id, RetentionPolicyData $data): array
    {
        $policy = $this->retention->update($id, $data);
        if ($policy === null) {
            throw new NotFoundException('Retention policy not found.');
        }
        return $policy->toArray();
    }

    public function deleteRetentionPolicy(int $id): void
    {
        if (!$this->retention->delete($id)) {
            throw new NotFoundException('Retention policy not found.');
        }
    }
}
