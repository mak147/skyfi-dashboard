<?php

declare(strict_types=1);

namespace SkyFi\Audit\DTOs;

final class CompliancePolicyData
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly string $policyType = 'custom',
        public readonly array $rules = [],
        public readonly int $isActive = 1,
        public readonly ?int $createdBy = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : null,
            policyType: (string) ($data['policy_type'] ?? 'custom'),
            rules: is_array($data['rules'] ?? null) ? $data['rules'] : [],
            isActive: isset($data['is_active']) ? (int) $data['is_active'] : 1,
            createdBy: isset($data['created_by']) ? (int) $data['created_by'] : null,
        );
    }
}
