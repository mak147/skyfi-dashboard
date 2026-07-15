<?php

declare(strict_types=1);

namespace SkyFi\Audit\Controllers;

use SkyFi\Audit\Contracts\ComplianceServiceContract;
use SkyFi\Audit\DTOs\CompliancePolicyData;
use SkyFi\Audit\DTOs\RetentionPolicyData;
use SkyFi\Audit\Validators\AuditValidator;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class ComplianceController
{
    public function __construct(
        private readonly ComplianceServiceContract $service,
        private readonly AuditValidator $validator,
        private readonly RequirePermissionMiddleware $auth,
    ) {}

    // ─── Compliance Policies ───────────────────────────────────────────

    public function listPolicies(Request $r): Response
    {
        $this->can($r, 'compliance.manage');
        $policies = $this->service->listPolicies();

        return new Response(200, [
            'data' => array_map(
                static fn(array $item) => [
                    'type' => 'compliance-policies',
                    'id' => (string) ($item['id'] ?? ''),
                    'attributes' => $item,
                ],
                $policies,
            ),
        ]);
    }

    public function getPolicy(Request $r): Response
    {
        $this->can($r, 'compliance.manage');
        $policy = $this->service->getPolicy($this->id($r));

        return ApiResponse::resource('compliance-policies', (string) ($policy['id'] ?? ''), $policy);
    }

    public function createPolicy(Request $r): Response
    {
        $userId = $this->can($r, 'compliance.manage');
        $body = $r->body();
        $this->validator->validateCompliancePolicy($body);

        $data = CompliancePolicyData::fromArray(array_merge($body, ['created_by' => $userId]));
        $policy = $this->service->createPolicy($data);

        return new Response(201, [
            'data' => [
                'type' => 'compliance-policies',
                'id' => (string) ($policy['id'] ?? ''),
                'attributes' => $policy,
            ],
        ]);
    }

    public function updatePolicy(Request $r): Response
    {
        $userId = $this->can($r, 'compliance.manage');
        $body = $r->body();
        $this->validator->validateCompliancePolicy($body);

        $data = CompliancePolicyData::fromArray(array_merge($body, ['created_by' => $userId]));
        $policy = $this->service->updatePolicy($this->id($r), $data);

        return ApiResponse::resource('compliance-policies', (string) ($policy['id'] ?? ''), $policy);
    }

    public function deletePolicy(Request $r): Response
    {
        $this->can($r, 'compliance.manage');
        $this->service->deletePolicy($this->id($r));
        return ApiResponse::noContent();
    }

    // ─── Retention Policies ────────────────────────────────────────────

    public function listRetentionPolicies(Request $r): Response
    {
        $this->can($r, 'compliance.manage');
        $policies = $this->service->listRetentionPolicies();

        return new Response(200, [
            'data' => array_map(
                static fn(array $item) => [
                    'type' => 'retention-policies',
                    'id' => (string) ($item['id'] ?? ''),
                    'attributes' => $item,
                ],
                $policies,
            ),
        ]);
    }

    public function getRetentionPolicy(Request $r): Response
    {
        $this->can($r, 'compliance.manage');
        $policy = $this->service->getRetentionPolicy($this->id($r));

        return ApiResponse::resource('retention-policies', (string) ($policy['id'] ?? ''), $policy);
    }

    public function createRetentionPolicy(Request $r): Response
    {
        $userId = $this->can($r, 'compliance.manage');
        $body = $r->body();
        $this->validator->validateRetentionPolicy($body);

        $data = RetentionPolicyData::fromArray(array_merge($body, ['created_by' => $userId]));
        $policy = $this->service->createRetentionPolicy($data);

        return new Response(201, [
            'data' => [
                'type' => 'retention-policies',
                'id' => (string) ($policy['id'] ?? ''),
                'attributes' => $policy,
            ],
        ]);
    }

    public function updateRetentionPolicy(Request $r): Response
    {
        $userId = $this->can($r, 'compliance.manage');
        $body = $r->body();
        $this->validator->validateRetentionPolicy($body);

        $data = RetentionPolicyData::fromArray(array_merge($body, ['created_by' => $userId]));
        $policy = $this->service->updateRetentionPolicy($this->id($r), $data);

        return ApiResponse::resource('retention-policies', (string) ($policy['id'] ?? ''), $policy);
    }

    public function deleteRetentionPolicy(Request $r): Response
    {
        $this->can($r, 'compliance.manage');
        $this->service->deleteRetentionPolicy($this->id($r));
        return ApiResponse::noContent();
    }

    private function can(Request $r, string $permission): int
    {
        $userId = (int) ($r->attributes()['claims']['sub'] ?? 0);
        $this->auth->authorize($userId, $permission);
        return $userId;
    }

    private function id(Request $r): int
    {
        return (int) ($r->attributes()['route_params']['id'] ?? 0);
    }
}
