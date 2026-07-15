<?php

declare(strict_types=1);

namespace SkyFi\Audit\Contracts;

use SkyFi\Audit\DTOs\CompliancePolicyData;
use SkyFi\Audit\DTOs\RetentionPolicyData;

interface ComplianceServiceContract
{
    /** @return list<array<string, mixed>> */
    public function listPolicies(): array;

    /** @return array<string, mixed> */
    public function getPolicy(int $id): array;

    /** @return array<string, mixed> */
    public function createPolicy(CompliancePolicyData $data): array;

    /** @return array<string, mixed> */
    public function updatePolicy(int $id, CompliancePolicyData $data): array;

    public function deletePolicy(int $id): void;

    /** @return list<array<string, mixed>> */
    public function listRetentionPolicies(): array;

    /** @return array<string, mixed> */
    public function getRetentionPolicy(int $id): array;

    /** @return array<string, mixed> */
    public function createRetentionPolicy(RetentionPolicyData $data): array;

    /** @return array<string, mixed> */
    public function updateRetentionPolicy(int $id, RetentionPolicyData $data): array;

    public function deleteRetentionPolicy(int $id): void;
}
