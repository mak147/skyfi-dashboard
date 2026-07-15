<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Services;

use SkyFi\Purchasing\Contracts\PurchaseRequestRepositoryContract;
use SkyFi\Purchasing\DomainModels\PurchaseRequest;
use SkyFi\Purchasing\DTOs\PurchaseRequestData;
use SkyFi\Purchasing\DTOs\PurchaseRequestListFilters;
use SkyFi\Purchasing\Validators\PurchaseRequestValidator;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Events\EventDispatcher;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class PurchaseRequestService
{
    public function __construct(
        private readonly PurchaseRequestRepositoryContract $repository,
        private readonly PurchaseRequestValidator $validator,
        private readonly AuditLoggerContract $audit,
    ) {
    }

    public function list(PurchaseRequestListFilters $filters): array
    {
        return $this->repository->list($filters);
    }

    public function get(int $id): PurchaseRequest
    {
        return $this->repository->find($id) ?? throw new NotFoundException('Purchase request not found.');
    }

    public function create(PurchaseRequestData $data, int $actorId, ?string $ip = null, ?string $agent = null): PurchaseRequest
    {
        $this->validator->validate($data);
        try {
            $request = $this->repository->create($data, $actorId);
        } catch (\PDOException $e) {
            throw new ValidationException([['code' => 'invalid_reference', 'detail' => 'A referenced product or user does not exist.']]);
        }
        $this->audit->log($actorId, 'purchasing.request.created', 'purchase_request', $request->id(), null, $request->toArray(), $ip, $agent);
        EventDispatcher::dispatch('purchasing.request.created', $request->toArray());
        return $this->get($request->id());
    }

    public function update(int $id, PurchaseRequestData $data, int $actorId, ?string $ip = null, ?string $agent = null): PurchaseRequest
    {
        $existing = $this->get($id);
        if (!in_array($existing->status(), ['draft', 'rejected'], true)) {
            throw new ValidationException([['code' => 'invalid_status', 'detail' => 'Only draft or rejected requests can be edited.']]);
        }
        $this->validator->validate($data);
        $old = $existing->toArray();
        $request = $this->repository->update($id, $data, $actorId);
        $this->audit->log($actorId, 'purchasing.request.updated', 'purchase_request', $id, $old, $request->toArray(), $ip, $agent);
        return $this->get($id);
    }

    public function submit(int $id, int $actorId, ?string $ip = null, ?string $agent = null): PurchaseRequest
    {
        $existing = $this->get($id);
        if (!in_array($existing->status(), ['draft', 'rejected'], true)) {
            throw new ValidationException([['code' => 'invalid_status', 'detail' => 'Only draft or rejected requests can be submitted for approval.']]);
        }
        $request = $this->repository->updateStatus($id, 'pending_approval', $actorId);
        $this->audit->log($actorId, 'purchasing.request.submitted', 'purchase_request', $id, $existing->toArray(), $request->toArray(), $ip, $agent);
        return $this->get($id);
    }

    public function approve(int $id, int $approverId, ?string $comments, ?string $ip = null, ?string $agent = null): PurchaseRequest
    {
        $existing = $this->get($id);
        if ($existing->status() !== 'pending_approval') {
            throw new ValidationException([['code' => 'invalid_status', 'detail' => 'Only pending requests can be approved.']]);
        }
        $this->repository->addApproval($id, $approverId, 'approved', $comments);
        $request = $this->repository->updateStatus($id, 'approved', $approverId);
        $this->audit->log($approverId, 'purchasing.request.approved', 'purchase_request', $id, $existing->toArray(), $request->toArray(), $ip, $agent);
        EventDispatcher::dispatch('purchasing.request.approved', $request->toArray());
        return $this->get($id);
    }

    public function reject(int $id, int $approverId, ?string $comments, ?string $ip = null, ?string $agent = null): PurchaseRequest
    {
        $existing = $this->get($id);
        if ($existing->status() !== 'pending_approval') {
            throw new ValidationException([['code' => 'invalid_status', 'detail' => 'Only pending requests can be rejected.']]);
        }
        $this->repository->addApproval($id, $approverId, 'rejected', $comments);
        $request = $this->repository->updateStatus($id, 'rejected', $approverId);
        $this->audit->log($approverId, 'purchasing.request.rejected', 'purchase_request', $id, $existing->toArray(), $request->toArray(), $ip, $agent);
        return $this->get($id);
    }

    public function cancel(int $id, int $actorId, ?string $ip = null, ?string $agent = null): PurchaseRequest
    {
        $existing = $this->get($id);
        if (in_array($existing->status(), ['converted', 'cancelled'], true)) {
            throw new ValidationException([['code' => 'invalid_status', 'detail' => 'This request cannot be cancelled.']]);
        }
        $request = $this->repository->updateStatus($id, 'cancelled', $actorId);
        $this->audit->log($actorId, 'purchasing.request.cancelled', 'purchase_request', $id, $existing->toArray(), $request->toArray(), $ip, $agent);
        return $this->get($id);
    }
}
