<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Services;

use SkyFi\Purchasing\Contracts\PurchaseOrderRepositoryContract;
use SkyFi\Purchasing\DomainModels\PurchaseOrder;
use SkyFi\Purchasing\DTOs\PurchaseOrderData;
use SkyFi\Purchasing\DTOs\PurchaseOrderListFilters;
use SkyFi\Purchasing\Validators\PurchaseOrderValidator;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Events\EventDispatcher;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class PurchaseOrderService
{
    public function __construct(
        private readonly PurchaseOrderRepositoryContract $repository,
        private readonly PurchaseOrderValidator $validator,
        private readonly AuditLoggerContract $audit,
        private readonly PurchasingFinanceIntegrationService $finance,
    ) {
    }

    public function list(PurchaseOrderListFilters $filters): array
    {
        return $this->repository->list($filters);
    }

    public function get(int $id): PurchaseOrder
    {
        return $this->repository->find($id) ?? throw new NotFoundException('Purchase order not found.');
    }

    public function create(PurchaseOrderData $data, int $actorId, ?string $ip = null, ?string $agent = null): PurchaseOrder
    {
        $this->validator->validate($data);
        try {
            $order = $this->repository->create($data, $actorId);
        } catch (\PDOException $e) {
            throw new ValidationException([['code' => 'invalid_reference', 'detail' => 'A referenced vendor, warehouse, or product does not exist.']]);
        }
        $this->audit->log($actorId, 'purchasing.order.created', 'purchase_order', $order->id(), null, $order->toArray(), $ip, $agent);
        EventDispatcher::dispatch('purchasing.order.created', $order->toArray());
        return $this->get($order->id());
    }

    public function update(int $id, PurchaseOrderData $data, int $actorId, ?string $ip = null, ?string $agent = null): PurchaseOrder
    {
        $existing = $this->get($id);
        if (!in_array($existing->status(), ['draft', 'rejected'], true)) {
            throw new ValidationException([['code' => 'invalid_status', 'detail' => 'Only draft or rejected orders can be edited.']]);
        }
        $this->validator->validate($data);
        $old = $existing->toArray();
        $order = $this->repository->update($id, $data, $actorId);
        $this->audit->log($actorId, 'purchasing.order.updated', 'purchase_order', $id, $old, $order->toArray(), $ip, $agent);
        return $this->get($id);
    }

    public function submit(int $id, int $actorId, ?string $ip = null, ?string $agent = null): PurchaseOrder
    {
        $existing = $this->get($id);
        if (!in_array($existing->status(), ['draft', 'rejected'], true)) {
            throw new ValidationException([['code' => 'invalid_status', 'detail' => 'Only draft or rejected orders can be submitted.']]);
        }
        $order = $this->repository->updateStatus($id, 'pending_approval', $actorId);
        $this->audit->log($actorId, 'purchasing.order.submitted', 'purchase_order', $id, $existing->toArray(), $order->toArray(), $ip, $agent);
        return $this->get($id);
    }

    public function approve(int $id, int $approverId, ?string $comments, ?string $ip = null, ?string $agent = null): PurchaseOrder
    {
        $existing = $this->get($id);
        if ($existing->status() !== 'pending_approval') {
            throw new ValidationException([['code' => 'invalid_status', 'detail' => 'Only pending orders can be approved.']]);
        }
        $this->repository->addApproval($id, $approverId, 'approved', $comments);
        $order = $this->repository->updateStatus($id, 'approved', $approverId);
        $this->audit->log($approverId, 'purchasing.order.approved', 'purchase_order', $id, $existing->toArray(), $order->toArray(), $ip, $agent);
        EventDispatcher::dispatch('purchasing.order.approved', $order->toArray());
        $this->finance->tryPostOrderApproved($order->id(), $approverId);
        return $this->get($id);
    }

    public function reject(int $id, int $approverId, ?string $comments, ?string $ip = null, ?string $agent = null): PurchaseOrder
    {
        $existing = $this->get($id);
        if ($existing->status() !== 'pending_approval') {
            throw new ValidationException([['code' => 'invalid_status', 'detail' => 'Only pending orders can be rejected.']]);
        }
        $this->repository->addApproval($id, $approverId, 'rejected', $comments);
        $order = $this->repository->updateStatus($id, 'rejected', $approverId);
        $this->audit->log($approverId, 'purchasing.order.rejected', 'purchase_order', $id, $existing->toArray(), $order->toArray(), $ip, $agent);
        return $this->get($id);
    }

    public function cancel(int $id, int $actorId, ?string $ip = null, ?string $agent = null): PurchaseOrder
    {
        $existing = $this->get($id);
        if (in_array($existing->status(), ['closed', 'cancelled', 'fully_received'], true)) {
            throw new ValidationException([['code' => 'invalid_status', 'detail' => 'This order cannot be cancelled.']]);
        }
        $order = $this->repository->updateStatus($id, 'cancelled', $actorId);
        $this->audit->log($actorId, 'purchasing.order.cancelled', 'purchase_order', $id, $existing->toArray(), $order->toArray(), $ip, $agent);
        return $this->get($id);
    }

    public function close(int $id, int $actorId, ?string $ip = null, ?string $agent = null): PurchaseOrder
    {
        $existing = $this->get($id);
        if (!in_array($existing->status(), ['approved', 'sent', 'partially_received', 'fully_received'], true)) {
            throw new ValidationException([['code' => 'invalid_status', 'detail' => 'This order cannot be closed.']]);
        }
        $order = $this->repository->updateStatus($id, 'closed', $actorId);
        $this->audit->log($actorId, 'purchasing.order.closed', 'purchase_order', $id, $existing->toArray(), $order->toArray(), $ip, $agent);
        return $this->get($id);
    }
}
