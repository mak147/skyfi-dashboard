<?php

declare(strict_types=1);

namespace SkyFi\Billing\Services;

use SkyFi\Billing\Contracts\BillingScheduleRepositoryContract;
use SkyFi\Billing\Contracts\InvoiceRepositoryContract;
use SkyFi\Billing\Contracts\InvoiceServiceContract;
use SkyFi\Billing\Data\BulkGenerateData;
use SkyFi\Billing\Data\CreateInvoiceData;
use SkyFi\Billing\Data\GenerateInvoiceData;
use SkyFi\Billing\Data\InvoiceListFilters;
use SkyFi\Billing\Data\UpdateInvoiceData;
use SkyFi\Billing\Models\Invoice;
use SkyFi\Connections\Contracts\ConnectionRepositoryContract;
use SkyFi\Packages\Contracts\PackageRepositoryContract;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class InvoiceService implements InvoiceServiceContract
{
    /** @var array<string> */
    private const VALID_STATUSES = ['draft', 'pending', 'issued', 'partially_paid', 'paid', 'overdue', 'cancelled', 'void'];

    /** @var array<string, array<string>> */
    private const VALID_STATUS_TRANSITIONS = [
        'draft' => ['pending', 'cancelled'],
        'pending' => ['issued', 'cancelled'],
        'issued' => ['partially_paid', 'paid', 'overdue', 'void'],
        'partially_paid' => ['paid', 'overdue', 'void'],
        'paid' => ['void'],
        'overdue' => ['paid', 'partially_paid', 'void'],
        'cancelled' => [],
        'void' => [],
    ];

    public function __construct(
        private readonly InvoiceRepositoryContract $invoiceRepository,
        private readonly BillingScheduleRepositoryContract $scheduleRepository,
        private readonly ConnectionRepositoryContract $connectionRepository,
        private readonly PackageRepositoryContract $packageRepository,
        private readonly AuditLoggerContract $auditLogger,
    ) {
    }

    public function list(InvoiceListFilters $filters): array
    {
        return $this->invoiceRepository->list($filters);
    }

    public function get(int $id): Invoice
    {
        $invoice = $this->invoiceRepository->findActive($id);
        if ($invoice === null) {
            throw new NotFoundException('Invoice not found.');
        }

        return $invoice;
    }

    public function create(CreateInvoiceData $data, int $authUserId, ?string $ip, ?string $ua): Invoice
    {
        $invoiceNumber = $this->generateInvoiceNumber();

        $subtotal = array_reduce($data->items, static fn(float $carry, array $item): float => $carry + $item['amount'], 0.0);
        $taxTotal = array_reduce($data->items, static fn(float $carry, array $item): float => $carry + $item['tax_amount'], 0.0);
        $discountTotal = array_reduce($data->items, static fn(float $carry, array $item): float => $carry + $item['discount_amount'], 0.0);
        $totalAmount = $subtotal + $taxTotal - $discountTotal + $data->previousBalance;

        $invoice = $this->invoiceRepository->create([
            'invoice_number' => $invoiceNumber,
            'customer_id' => $data->customerId,
            'connection_id' => $data->connectionId,
            'package_id' => $data->packageId,
            'status' => 'draft',
            'billing_period_start' => $data->billingPeriodStart,
            'billing_period_end' => $data->billingPeriodEnd,
            'issue_date' => $data->issueDate,
            'due_date' => $data->dueDate,
            'currency' => 'PKR',
            'subtotal' => $subtotal,
            'tax_amount' => $taxTotal,
            'discount_amount' => $discountTotal,
            'late_fee_amount' => 0.00,
            'previous_balance' => $data->previousBalance,
            'total_amount' => $totalAmount,
            'balance_due' => $totalAmount,
            'notes' => $data->notes,
            'created_by' => $authUserId,
        ]);

        $this->invoiceRepository->addItems($invoice->id, $data->items);
        $this->invoiceRepository->addActivity($invoice->id, 'created', 'Invoice created manually.', $authUserId);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'create',
            entityType: 'invoice',
            entityId: $invoice->id,
            oldValues: null,
            newValues: $invoice->toArray(),
            ipAddress: $ip,
            userAgent: $ua,
        );

        return $this->get($invoice->id);
    }

    public function update(int $id, UpdateInvoiceData $data, int $authUserId, ?string $ip, ?string $ua): Invoice
    {
        $invoice = $this->get($id);

        if (!in_array($invoice->status, ['draft', 'pending'], true)) {
            throw new ValidationException([
                ['code' => 'immutable', 'detail' => 'Only draft or pending invoices can be updated.', 'source' => ['pointer' => '/data/attributes/status']],
            ]);
        }

        $updateData = [];
        if ($data->notes !== null) {
            $updateData['notes'] = $data->notes;
        }
        if ($data->dueDate !== null) {
            $updateData['due_date'] = $data->dueDate;
        }

        $oldValues = $invoice->toArray();

        if ($data->items !== null) {
            $this->invoiceRepository->deleteItems($id);
            $this->invoiceRepository->addItems($id, $data->items);

            $subtotal = array_reduce($data->items, static fn(float $carry, array $item): float => $carry + $item['amount'], 0.0);
            $taxTotal = array_reduce($data->items, static fn(float $carry, array $item): float => $carry + $item['tax_amount'], 0.0);
            $discountTotal = array_reduce($data->items, static fn(float $carry, array $item): float => $carry + $item['discount_amount'], 0.0);
            $totalAmount = $subtotal + $taxTotal - $discountTotal + $invoice->previousBalance;

            $updateData['subtotal'] = $subtotal;
            $updateData['tax_amount'] = $taxTotal;
            $updateData['discount_amount'] = $discountTotal;
            $updateData['total_amount'] = $totalAmount;
            $updateData['balance_due'] = $totalAmount;
        }

        $updateData['updated_by'] = $authUserId;

        $invoice = $this->invoiceRepository->update($id, $updateData);
        $this->invoiceRepository->addActivity($id, 'updated', 'Invoice updated.', $authUserId);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'update',
            entityType: 'invoice',
            entityId: $id,
            oldValues: $oldValues,
            newValues: $invoice->toArray(),
            ipAddress: $ip,
            userAgent: $ua,
        );

        return $this->get($id);
    }

    public function delete(int $id, int $authUserId, ?string $ip, ?string $ua): void
    {
        $invoice = $this->get($id);

        $this->invoiceRepository->softDelete($id);
        $this->invoiceRepository->addActivity($id, 'deleted', 'Invoice deleted.', $authUserId);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'delete',
            entityType: 'invoice',
            entityId: $id,
            oldValues: $invoice->toArray(),
            newValues: null,
            ipAddress: $ip,
            userAgent: $ua,
        );
    }

    public function changeStatus(int $id, string $status, int $authUserId, ?string $ip, ?string $ua): Invoice
    {
        $invoice = $this->get($id);

        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new ValidationException([
                ['code' => 'invalid_status', 'detail' => 'The provided status is not valid.', 'source' => ['pointer' => '/data/attributes/status']],
            ]);
        }

        $allowedTransitions = self::VALID_STATUS_TRANSITIONS[$invoice->status] ?? [];
        if (!in_array($status, $allowedTransitions, true)) {
            throw new ValidationException([
                ['code' => 'invalid_transition', 'detail' => "Cannot transition from '{$invoice->status}' to '{$status}'.", 'source' => ['pointer' => '/data/attributes/status']],
            ]);
        }

        $oldValues = ['status' => $invoice->status];

        $this->invoiceRepository->updateStatus($id, $status);
        $this->invoiceRepository->addActivity($id, 'status_changed', "Status changed from {$invoice->status} to {$status}.", $authUserId);

        $updated = $this->get($id);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'status_change',
            entityType: 'invoice',
            entityId: $id,
            oldValues: $oldValues,
            newValues: ['status' => $status],
            ipAddress: $ip,
            userAgent: $ua,
        );

        return $updated;
    }

    public function generate(GenerateInvoiceData $data, int $authUserId, ?string $ip, ?string $ua): Invoice
    {
        $schedule = $this->scheduleRepository->findByConnection($data->connectionId);
        if ($schedule === null) {
            throw new NotFoundException('Billing schedule not found for this connection.');
        }

        $connection = $this->connectionRepository->find($data->connectionId);
        if ($connection === null) {
            throw new NotFoundException('Connection not found.');
        }

        $connectionData = $connection->toArray();

        if ($connectionData['status'] !== 'active') {
            throw new ValidationException([
                ['code' => 'inactive_connection', 'detail' => 'Cannot generate invoice for an inactive connection.', 'source' => ['pointer' => '/data/attributes/connection_id']],
            ]);
        }

        // Fetch package price for the billing cycle
        $unitPrice = $this->packageRepository->getPrice($connectionData['package_id'], $schedule->billingCycle);

        // Determine billing period
        $billingStart = $data->billingPeriodStart ?? $schedule->nextBillDate;
        $billingEnd = $data->billingPeriodEnd ?? $this->calculatePeriodEnd($billingStart, $schedule->billingCycle, $schedule->customIntervalDays);
        $issueDate = $data->issueDate ?? date('Y-m-d');
        $dueDate = $data->dueDate ?? date('Y-m-d', strtotime($issueDate . ' + ' . max(1, $schedule->gracePeriodDays) . ' days'));

        // Build invoice items
        $items = [];
        $items[] = [
            'item_type' => 'recurring',
            'description' => ($connectionData['package_name'] ?? 'Package') . ' (' . $schedule->billingCycle . ') — ' . $billingStart . ' to ' . $billingEnd,
            'quantity' => 1.0,
            'unit_price' => $unitPrice,
            'amount' => $unitPrice,
            'tax_amount' => 0.0,
            'discount_amount' => 0.0,
        ];

        $subtotal = $unitPrice;
        $totalAmount = $subtotal;

        $invoiceNumber = $this->generateInvoiceNumber();

        $invoice = $this->invoiceRepository->create([
            'invoice_number' => $invoiceNumber,
            'customer_id' => (int) $connectionData['customer_id'],
            'connection_id' => $data->connectionId,
            'package_id' => (int) $connectionData['package_id'],
            'status' => 'draft',
            'billing_period_start' => $billingStart,
            'billing_period_end' => $billingEnd,
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'currency' => 'PKR',
            'subtotal' => $subtotal,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'late_fee_amount' => 0.00,
            'previous_balance' => 0.00,
            'total_amount' => $totalAmount,
            'balance_due' => $totalAmount,
            'notes' => $data->notes,
            'created_by' => $authUserId,
        ]);

        $this->invoiceRepository->addItems($invoice->id, $items);
        $this->invoiceRepository->addActivity($invoice->id, 'created', 'Invoice generated automatically.', $authUserId);

        // Update next billing date
        $newNextBillDate = $this->calculateNextBillDate($schedule->nextBillDate, $schedule->billingCycle, $schedule->customIntervalDays);
        $this->scheduleRepository->updateNextBillDate($data->connectionId, $newNextBillDate);

        // Update connection next_billing_date for backward compatibility
        $this->connectionRepository->update($data->connectionId, ['next_billing_date' => $newNextBillDate]);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'generate',
            entityType: 'invoice',
            entityId: $invoice->id,
            oldValues: null,
            newValues: $invoice->toArray(),
            ipAddress: $ip,
            userAgent: $ua,
        );

        \SkyFi\Shared\Events\EventDispatcher::dispatch('invoice.generated', $invoice->toArray());

        return $this->get($invoice->id);
    }

    public function bulkGenerate(BulkGenerateData $data, int $authUserId, ?string $ip, ?string $ua): array
    {
        $billingDate = $data->billingDate ?? date('Y-m-d');
        $schedules = $this->scheduleRepository->findDue($billingDate, $data->connectionIds);

        $generated = 0;
        $failed = 0;
        $errors = [];

        foreach ($schedules as $schedule) {
            try {
                $this->generate(
                    new GenerateInvoiceData(
                        connectionId: $schedule->connectionId,
                        billingPeriodStart: null,
                        billingPeriodEnd: null,
                        issueDate: null,
                        dueDate: null,
                        notes: null,
                    ),
                    $authUserId,
                    $ip,
                    $ua,
                );
                $generated++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = [
                    'connection_id' => $schedule->connectionId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'generated' => $generated,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    public function statistics(): array
    {
        return $this->invoiceRepository->statistics();
    }

    public function activity(int $id): array
    {
        $invoice = $this->get($id);
        return $this->invoiceRepository->getActivities($id);
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . date('Ymd') . '-';
        $attempts = 0;

        do {
            $code = $prefix . strtoupper(bin2hex(random_bytes(2)));
            $attempts++;
        } while ($this->invoiceRepository->numberExists($code) && $attempts < 10);

        return $code;
    }

    private function calculatePeriodEnd(string $start, string $cycle, ?int $customDays): string
    {
        return match ($cycle) {
            'monthly' => date('Y-m-d', strtotime($start . ' +1 month -1 day')),
            'quarterly' => date('Y-m-d', strtotime($start . ' +3 months -1 day')),
            'semi_annual' => date('Y-m-d', strtotime($start . ' +6 months -1 day')),
            'annual' => date('Y-m-d', strtotime($start . ' +1 year -1 day')),
            'custom' => $customDays !== null ? date('Y-m-d', strtotime($start . " +{$customDays} days -1 day")) : date('Y-m-d', strtotime($start . ' +1 month -1 day')),
            default => date('Y-m-d', strtotime($start . ' +1 month -1 day')),
        };
    }

    private function calculateNextBillDate(string $current, string $cycle, ?int $customDays): string
    {
        return match ($cycle) {
            'monthly' => date('Y-m-d', strtotime($current . ' +1 month')),
            'quarterly' => date('Y-m-d', strtotime($current . ' +3 months')),
            'semi_annual' => date('Y-m-d', strtotime($current . ' +6 months')),
            'annual' => date('Y-m-d', strtotime($current . ' +1 year')),
            'custom' => $customDays !== null ? date('Y-m-d', strtotime($current . " +{$customDays} days")) : date('Y-m-d', strtotime($current . ' +1 month')),
            default => date('Y-m-d', strtotime($current . ' +1 month')),
        };
    }
}
}
