<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Billing;

use PHPUnit\Framework\TestCase;
use SkyFi\Billing\Contracts\BillingScheduleRepositoryContract;
use SkyFi\Billing\Contracts\InvoiceRepositoryContract;
use SkyFi\Billing\Data\CreateInvoiceData;
use SkyFi\Billing\Data\UpdateInvoiceData;
use SkyFi\Billing\Models\Invoice;
use SkyFi\Billing\Services\InvoiceService;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

/**
 * Service and calculation tests for the Billing / Invoice module.
 *
 * @group unit
 * @group Billing
 */
final class BillingTest extends TestCase
{
    public function testGetThrowsNotFoundExceptionWhenInvoiceDoesNotExist(): void
    {
        $repo = $this->createMock(InvoiceRepositoryContract::class);
        $repo->method('findActive')->willReturn(null);

        $scheduleRepo = $this->createMock(BillingScheduleRepositoryContract::class);
        $audit = $this->createMock(AuditLoggerContract::class);
        $service = new InvoiceService($repo, $scheduleRepo, $audit);

        $this->expectException(NotFoundException::class);
        $service->get(999);
    }

    public function testGetReturnsInvoiceWhenExists(): void
    {
        $invoice = new Invoice(
            id: 101,
            invoiceNumber: 'INV-12345',
            customerId: 42,
            customerName: 'Muhammad Ali',
            connectionId: 5,
            packageId: 2,
            packageName: '100 Mbps Unlimited Fiber',
            status: 'draft',
            billingPeriodStart: '2026-07-01',
            billingPeriodEnd: '2026-07-31',
            issueDate: '2026-07-15',
            dueDate: '2026-07-30',
            currency: 'PKR',
            subtotal: 2500.00,
            taxAmount: 0.00,
            discountAmount: 0.00,
            lateFeeAmount: 0.00,
            previousBalance: 0.00,
            totalAmount: 2500.00,
            balanceDue: 2500.00,
            notes: 'Test invoice',
            createdAt: '2026-07-15 12:00:00',
            createdBy: 1,
            updatedAt: null,
            updatedBy: null
        );

        $repo = $this->createMock(InvoiceRepositoryContract::class);
        $repo->method('findActive')->with(101)->willReturn($invoice);

        $scheduleRepo = $this->createMock(BillingScheduleRepositoryContract::class);
        $audit = $this->createMock(AuditLoggerContract::class);
        $service = new InvoiceService($repo, $scheduleRepo, $audit);

        $result = $service->get(101);
        $this->assertSame(101, $result->id);
        $this->assertSame('INV-12345', $result->invoiceNumber);
    }

    public function testChangeStatusValidatesTransitions(): void
    {
        $invoice = new Invoice(
            id: 101,
            invoiceNumber: 'INV-12345',
            customerId: 42,
            customerName: 'Muhammad Ali',
            connectionId: 5,
            packageId: 2,
            packageName: '100 Mbps Unlimited Fiber',
            status: 'draft',
            billingPeriodStart: '2026-07-01',
            billingPeriodEnd: '2026-07-31',
            issueDate: '2026-07-15',
            dueDate: '2026-07-30',
            currency: 'PKR',
            subtotal: 2500.00,
            taxAmount: 0.00,
            discountAmount: 0.00,
            lateFeeAmount: 0.00,
            previousBalance: 0.00,
            totalAmount: 2500.00,
            balanceDue: 2500.00,
            notes: 'Test invoice',
            createdAt: '2026-07-15 12:00:00',
            createdBy: 1,
            updatedAt: null,
            updatedBy: null
        );

        $repo = $this->createMock(InvoiceRepositoryContract::class);
        $repo->method('findActive')->with(101)->willReturn($invoice);

        $scheduleRepo = $this->createMock(BillingScheduleRepositoryContract::class);
        $audit = $this->createMock(AuditLoggerContract::class);
        $service = new InvoiceService($repo, $scheduleRepo, $audit);

        // 'draft' can transition to 'pending' or 'cancelled'
        // Let's test a valid transition
        $repo->expects($this->once())->method('updateStatus')->with(101, 'pending');
        $service->changeStatus(101, 'pending', 1, '127.0.0.1', 'test');

        // Let's expect failure for an invalid transition (draft to paid is invalid)
        $this->expectException(ValidationException::class);
        $service->changeStatus(101, 'paid', 1, '127.0.0.1', 'test');
    }

    public function testUpdateThrowsValidationExceptionForLockedStatuses(): void
    {
        $invoice = new Invoice(
            id: 101,
            invoiceNumber: 'INV-12345',
            customerId: 42,
            customerName: 'Muhammad Ali',
            connectionId: 5,
            packageId: 2,
            packageName: '100 Mbps Unlimited Fiber',
            status: 'paid', // Status 'paid' is locked and immutable
            billingPeriodStart: '2026-07-01',
            billingPeriodEnd: '2026-07-31',
            issueDate: '2026-07-15',
            dueDate: '2026-07-30',
            currency: 'PKR',
            subtotal: 2500.00,
            taxAmount: 0.00,
            discountAmount: 0.00,
            lateFeeAmount: 0.00,
            previousBalance: 0.00,
            totalAmount: 2500.00,
            balanceDue: 2500.00,
            notes: 'Test invoice',
            createdAt: '2026-07-15 12:00:00',
            createdBy: 1,
            updatedAt: null,
            updatedBy: null
        );

        $repo = $this->createMock(InvoiceRepositoryContract::class);
        $repo->method('findActive')->with(101)->willReturn($invoice);

        $scheduleRepo = $this->createMock(BillingScheduleRepositoryContract::class);
        $audit = $this->createMock(AuditLoggerContract::class);
        $service = new InvoiceService($repo, $scheduleRepo, $audit);

        $this->expectException(ValidationException::class);
        $service->update(101, UpdateInvoiceData::fromArray([
            'notes' => 'Changing notes on a paid invoice should fail',
        ]), 1, '127.0.0.1', 'test');
    }
}
