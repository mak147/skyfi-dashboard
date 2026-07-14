<?php

declare(strict_types=1);

namespace SkyFi\Customers\Services;

use SkyFi\Customers\Contracts\CustomerRepositoryContract;
use SkyFi\Customers\Contracts\CustomerServiceContract;
use SkyFi\Customers\Data\CreateCustomerData;
use SkyFi\Customers\Data\CustomerListFilters;
use SkyFi\Customers\Data\UpdateCustomerData;
use SkyFi\Customers\Models\Customer;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class CustomerService implements CustomerServiceContract
{
    /** @var array<string> */
    private const VALID_STATUSES = ['lead', 'prospect', 'active', 'suspended', 'disconnected', 'archived'];

    /** @var array<string, array<string>> */
    private const VALID_STATUS_TRANSITIONS = [
        'lead' => ['prospect', 'active', 'archived'],
        'prospect' => ['lead', 'active', 'archived'],
        'active' => ['suspended', 'disconnected', 'archived'],
        'suspended' => ['active', 'disconnected', 'archived'],
        'disconnected' => ['active', 'suspended', 'archived'],
        'archived' => ['lead', 'prospect'],
    ];

    public function __construct(
        private readonly CustomerRepositoryContract $repository,
        private readonly AuditLoggerContract $auditLogger,
    ) {
    }

    public function list(CustomerListFilters $filters): array
    {
        return $this->repository->list($filters);
    }

    public function get(int $id): Customer
    {
        $customer = $this->repository->findActive($id);
        if ($customer === null) {
            throw new NotFoundException('Customer not found.');
        }

        return $customer;
    }

    public function create(CreateCustomerData $data, int $authUserId, ?string $ip, ?string $ua): Customer
    {
        $this->validateUniqueFields($data->cnic, null);

        $customerCode = $this->generateCustomerCode();

        $customer = $this->repository->create([
            'customer_code' => $customerCode,
            'full_name' => $data->fullName,
            'father_husband_name' => $data->fatherHusbandName,
            'cnic' => $data->cnic,
            'phone' => $data->phone,
            'whatsapp' => $data->whatsapp,
            'email' => $data->email,
            'address' => $data->address,
            'city' => $data->city,
            'area' => $data->area,
            'notes' => $data->notes,
            'status' => 'lead',
            'registration_date' => $data->registrationDate,
            'installation_date' => $data->installationDate,
            'installation_technician_id' => $data->installationTechnicianId,
            'emergency_contact_name' => $data->emergencyContactName,
            'emergency_contact_phone' => $data->emergencyContactPhone,
            'created_by' => $authUserId,
            'updated_by' => null,
        ]);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'create',
            entityType: 'customer',
            entityId: $customer->id,
            oldValues: null,
            newValues: $customer->toArray(),
            ipAddress: $ip,
            userAgent: $ua,
        );

        return $customer;
    }

    public function update(int $id, UpdateCustomerData $data, int $authUserId, ?string $ip, ?string $ua): Customer
    {
        $existing = $this->get($id);
        $this->validateUniqueFields($data->cnic, $id);

        $oldValues = $existing->toArray();

        $customer = $this->repository->update($id, [
            'full_name' => $data->fullName,
            'father_husband_name' => $data->fatherHusbandName,
            'cnic' => $data->cnic,
            'phone' => $data->phone,
            'whatsapp' => $data->whatsapp,
            'email' => $data->email,
            'address' => $data->address,
            'city' => $data->city,
            'area' => $data->area,
            'notes' => $data->notes,
            'registration_date' => $data->registrationDate,
            'installation_date' => $data->installationDate,
            'installation_technician_id' => $data->installationTechnicianId,
            'emergency_contact_name' => $data->emergencyContactName,
            'emergency_contact_phone' => $data->emergencyContactPhone,
            'updated_by' => $authUserId,
        ]);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'update',
            entityType: 'customer',
            entityId: $id,
            oldValues: $oldValues,
            newValues: $customer->toArray(),
            ipAddress: $ip,
            userAgent: $ua,
        );

        return $customer;
    }

    public function delete(int $id, int $authUserId, ?string $ip, ?string $ua): void
    {
        $existing = $this->get($id);

        $this->repository->softDelete($id);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'delete',
            entityType: 'customer',
            entityId: $id,
            oldValues: $existing->toArray(),
            newValues: null,
            ipAddress: $ip,
            userAgent: $ua,
        );
    }

    public function changeStatus(int $id, string $newStatus, int $authUserId, ?string $ip, ?string $ua): Customer
    {
        $customer = $this->get($id);

        if (!in_array($newStatus, self::VALID_STATUSES, true)) {
            throw new ValidationException([
                ['code' => 'invalid_status', 'detail' => 'The provided status is not valid.', 'source' => ['pointer' => '/data/attributes/status']],
            ]);
        }

        $allowedTransitions = self::VALID_STATUS_TRANSITIONS[$customer->status] ?? [];
        if (!in_array($newStatus, $allowedTransitions, true)) {
            throw new ValidationException([
                ['code' => 'invalid_transition', 'detail' => "Cannot transition from '{$customer->status}' to '{$newStatus}'.", 'source' => ['pointer' => '/data/attributes/status']],
            ]);
        }

        $oldValues = ['status' => $customer->status];

        $this->repository->updateStatus($id, $newStatus);
        $updated = $this->get($id);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'status_change',
            entityType: 'customer',
            entityId: $id,
            oldValues: $oldValues,
            newValues: ['status' => $newStatus],
            ipAddress: $ip,
            userAgent: $ua,
        );

        return $updated;
    }

    private function validateUniqueFields(?string $cnic, ?int $excludeId): void
    {
        $errors = [];

        if ($cnic !== null && $this->repository->cnicExists($cnic, $excludeId)) {
            $errors[] = ['code' => 'unique', 'detail' => 'The CNIC has already been taken.', 'source' => ['pointer' => '/data/attributes/cnic']];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    private function generateCustomerCode(): string
    {
        $prefix = 'SKY-';
        $attempts = 0;

        do {
            $code = $prefix . strtoupper(bin2hex(random_bytes(3)));
            $attempts++;
        } while ($this->repository->codeExists($code) && $attempts < 10);

        return $code;
    }
}
