<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Customers;

use PHPUnit\Framework\TestCase;
use SkyFi\Customers\Contracts\CustomerRepositoryContract;
use SkyFi\Customers\Data\CreateCustomerData;
use SkyFi\Customers\Data\CustomerListFilters;
use SkyFi\Customers\Data\UpdateCustomerData;
use SkyFi\Customers\Models\Customer;
use SkyFi\Customers\Services\CustomerService;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

/**
 * Service and validation tests for the Customers module.
 *
 * @group unit
 * @group Customers
 */
final class CustomersTest extends TestCase
{
    public function testGetThrowsNotFoundExceptionWhenCustomerDoesNotExist(): void
    {
        $repo = $this->createMock(CustomerRepositoryContract::class);
        $repo->method('findActive')->willReturn(null);

        $audit = $this->createMock(AuditLoggerContract::class);
        $service = new CustomerService($repo, $audit);

        $this->expectException(NotFoundException::class);
        $service->get(999);
    }

    public function testGetReturnsCustomerWhenExists(): void
    {
        $customer = new Customer(
            id: 42,
            customerCode: 'SKY-12345',
            fullName: 'Muhammad Ali',
            fatherHusbandName: 'Ali Khan',
            cnic: '37405-1234567-1',
            phone: '+923001234567',
            whatsapp: '+923001234567',
            email: 'ali@example.com',
            address: 'Office 1, Saddar',
            city: 'Rawalpindi',
            area: 'Saddar',
            notes: 'VIP Customer',
            status: 'lead',
            registrationDate: '2026-07-15',
            installationDate: null,
            installationTechnicianId: null,
            emergencyContactName: null,
            emergencyContactPhone: null,
            createdAt: '2026-07-15 12:00:00',
            createdBy: 1,
            updatedAt: null,
            updatedBy: null
        );

        $repo = $this->createMock(CustomerRepositoryContract::class);
        $repo->method('findActive')->with(42)->willReturn($customer);

        $audit = $this->createMock(AuditLoggerContract::class);
        $service = new CustomerService($repo, $audit);

        $result = $service->get(42);
        $this->assertSame(42, $result->id);
        $this->assertSame('Muhammad Ali', $result->fullName);
    }

    public function testChangeStatusValidatesTransitions(): void
    {
        $customer = new Customer(
            id: 42,
            customerCode: 'SKY-12345',
            fullName: 'Muhammad Ali',
            fatherHusbandName: 'Ali Khan',
            cnic: '37405-1234567-1',
            phone: '+923001234567',
            whatsapp: '+923001234567',
            email: 'ali@example.com',
            address: 'Office 1, Saddar',
            city: 'Rawalpindi',
            area: 'Saddar',
            notes: 'VIP Customer',
            status: 'lead',
            registrationDate: '2026-07-15',
            installationDate: null,
            installationTechnicianId: null,
            emergencyContactName: null,
            emergencyContactPhone: null,
            createdAt: '2026-07-15 12:00:00',
            createdBy: 1,
            updatedAt: null,
            updatedBy: null
        );

        $repo = $this->createMock(CustomerRepositoryContract::class);
        $repo->method('findActive')->with(42)->willReturn($customer);

        $audit = $this->createMock(AuditLoggerContract::class);
        $service = new CustomerService($repo, $audit);

        // 'lead' can transition to 'prospect' or 'active' or 'archived'
        // Let's test a valid transition
        $repo->expects($this->once())->method('updateStatus')->with(42, 'prospect');
        $service->changeStatus(42, 'prospect', 1, '127.0.0.1', 'test');

        // Let's expect failure for an invalid transition (lead to disconnected is invalid)
        $this->expectException(ValidationException::class);
        $service->changeStatus(42, 'disconnected', 1, '127.0.0.1', 'test');
    }
}
