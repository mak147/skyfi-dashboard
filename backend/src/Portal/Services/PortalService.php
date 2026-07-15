<?php

declare(strict_types=1);

namespace SkyFi\Portal\Services;

use PDO;
use SkyFi\Billing\Contracts\InvoiceServiceContract;
use SkyFi\Billing\Data\InvoiceListFilters;
use SkyFi\Connections\Contracts\ConnectionServiceContract;
use SkyFi\Connections\Data\ConnectionListFilters;
use SkyFi\Customers\Contracts\CustomerServiceContract;
use SkyFi\Customers\Data\UpdateCustomerData;
use SkyFi\Notifications\Contracts\NotificationServiceContract;
use SkyFi\Notifications\DTOs\NotificationListFilters;
use SkyFi\Notifications\DTOs\UserPreferenceData;
use SkyFi\Notifications\Services\PreferenceService;
use SkyFi\Packages\Contracts\PackageServiceContract;
use SkyFi\Payments\Contracts\PaymentServiceContract;
use SkyFi\Payments\DTOs\PaymentListFilters;
use SkyFi\Portal\Contracts\PortalServiceContract;
use SkyFi\Portal\DTOs\CreateTicketData;
use SkyFi\Portal\DTOs\ReplyTicketData;
use SkyFi\Portal\DTOs\UpdatePreferenceData;
use SkyFi\Portal\DTOs\UpdateProfileData;
use SkyFi\Portal\Validators\PortalValidator;
use SkyFi\Shared\Auth\Contracts\UserRepositoryContract;
use SkyFi\Shared\Exceptions\AuthorizationException;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Support\Contracts\TicketServiceContract;
use SkyFi\Support\DTOs\CreateCommentData;
use SkyFi\Support\DTOs\CreateTicketData as SupportCreateTicketData;
use SkyFi\Support\DTOs\TicketListFilters;

final class PortalService implements PortalServiceContract
{
    public function __construct(
        private readonly UserRepositoryContract $users,
        private readonly CustomerServiceContract $customers,
        private readonly ConnectionServiceContract $connections,
        private readonly InvoiceServiceContract $invoices,
        private readonly PaymentServiceContract $payments,
        private readonly TicketServiceContract $tickets,
        private readonly NotificationServiceContract $notifications,
        private readonly PreferenceService $preferences,
        private readonly PackageServiceContract $packages,
        private readonly PortalValidator $validator,
        private readonly PDO $pdo,
    ) {
    }

    public function dashboard(int $userId): array
    {
        $customerId = $this->requireCustomerId($userId);
        $customer = $this->customers->get($customerId);

        $connection = $this->findPrimaryConnection($customerId);
        $package = null;
        if ($connection !== null && isset($connection->toArray()['package_id'])) {
            try {
                $package = $this->packages->get((int) $connection->toArray()['package_id'])->toArray();
            } catch (NotFoundException) {
                $package = null;
            }
        }

        $latestInvoice = $this->latestInvoice($customerId);
        $recentPayments = $this->recentPayments($customerId);
        $activeTickets = $this->activeTickets($customerId);
        $recentNotifications = $this->recentNotifications($userId);

        return [
            'customer' => $customer->toArray(),
            'connection' => $connection?->toArray(),
            'package' => $package,
            'latest_invoice' => $latestInvoice?->toArray(),
            'recent_payments' => array_map(static fn ($p) => $p->toArray(), $recentPayments),
            'active_tickets' => array_map(static fn ($t) => $t->toArray(), $activeTickets),
            'recent_notifications' => array_map(static fn ($n) => $n->toArray(), $recentNotifications),
            'outstanding_balance' => $this->calculateBalance($customerId),
            'is_online' => $this->isCustomerOnline($customerId),
        ];
    }

    public function connection(int $userId): array
    {
        $customerId = $this->requireCustomerId($userId);
        $connection = $this->findPrimaryConnection($customerId);

        if ($connection === null) {
            throw new NotFoundException('No active connection found for this account.');
        }

        $connectionArray = $connection->toArray();
        $package = null;
        if (isset($connectionArray['package_id'])) {
            try {
                $package = $this->packages->get((int) $connectionArray['package_id'])->toArray();
            } catch (NotFoundException) {
                $package = null;
            }
        }

        // Sanitize router information: expose only safe subset.
        $router = null;
        if (!empty($connectionArray['assigned_router'])) {
            $router = [
                'name' => $connectionArray['assigned_router'],
                'pop_site' => $connectionArray['pop_site'] ?? null,
                'tower' => $connectionArray['tower'] ?? null,
                'sector' => $connectionArray['sector'] ?? null,
            ];
        }

        return [
            'connection' => $connectionArray,
            'package' => $package,
            'router' => $router,
            'monthly_usage' => [
                'status' => 'placeholder',
                'message' => 'Usage analytics will be provided by the monitoring module.',
            ],
        ];
    }

    public function invoices(int $userId, array $query): array
    {
        $customerId = $this->requireCustomerId($userId);
        $query['filter']['customer_id'] = (string) $customerId;
        $filters = InvoiceListFilters::fromQuery($query);
        $result = $this->invoices->list($filters);

        return [
            'items' => array_map(static fn ($i) => $i->toArray(), $result['items']),
            'meta' => [
                'current_page' => $result['page'],
                'per_page' => $result['perPage'],
                'total' => $result['total'],
                'last_page' => $result['lastPage'],
            ],
        ];
    }

    public function invoice(int $userId, int $invoiceId): array
    {
        $customerId = $this->requireCustomerId($userId);
        $invoice = $this->invoices->get($invoiceId);

        if ((int) $invoice->toArray()['customer_id'] !== $customerId) {
            throw new AuthorizationException('You do not have permission to view this invoice.');
        }

        return $invoice->toArray();
    }

    public function balance(int $userId): array
    {
        $customerId = $this->requireCustomerId($userId);

        return [
            'outstanding_balance' => $this->calculateBalance($customerId),
            'currency' => 'PKR',
        ];
    }

    public function payments(int $userId, array $query): array
    {
        $customerId = $this->requireCustomerId($userId);
        $query['filter']['customer_id'] = (string) $customerId;
        $filters = PaymentListFilters::fromQuery($query);
        $result = $this->payments->list($filters);

        return [
            'items' => array_map(static fn ($p) => $p->toArray(), $result['items']),
            'meta' => [
                'current_page' => $result['page'],
                'per_page' => $result['perPage'],
                'total' => $result['total'],
                'last_page' => $result['lastPage'],
            ],
        ];
    }

    public function payment(int $userId, int $paymentId): array
    {
        $customerId = $this->requireCustomerId($userId);
        $payment = $this->payments->get($paymentId)->toArray();

        if ((int) $payment['customer_id'] !== $customerId) {
            throw new AuthorizationException('You do not have permission to view this payment.');
        }

        return $payment;
    }

    public function tickets(int $userId, array $query): array
    {
        $customerId = $this->requireCustomerId($userId);
        $query['filter']['customer_id'] = (string) $customerId;
        $filters = TicketListFilters::fromQuery($query);
        $result = $this->tickets->list($filters);

        return [
            'items' => array_map(static fn ($t) => $t->toArray(), $result['items']),
            'meta' => [
                'current_page' => $result['page'],
                'per_page' => $result['perPage'],
                'total' => $result['total'],
                'last_page' => $result['lastPage'],
            ],
        ];
    }

    public function ticket(int $userId, int $ticketId): array
    {
        $customerId = $this->requireCustomerId($userId);
        $detail = $this->tickets->get($ticketId);

        if ((int) $detail['ticket']['customer_id'] !== $customerId) {
            throw new AuthorizationException('You do not have permission to view this ticket.');
        }

        return $detail;
    }

    public function createTicket(int $userId, CreateTicketData $data): array
    {
        $customerId = $this->requireCustomerId($userId);
        $this->validator->validateTicket($data);

        $connectionId = $data->connectionId;
        $packageId = null;
        if ($connectionId !== null) {
            $connection = $this->connections->get($connectionId);
            if ((int) $connection->toArray()['customer_id'] !== $customerId) {
                throw new AuthorizationException('Invalid connection selected.');
            }
            $packageId = isset($connection->toArray()['package_id']) ? (int) $connection->toArray()['package_id'] : null;
        }

        $supportData = new SupportCreateTicketData(
            customerId: $customerId,
            connectionId: $connectionId,
            packageId: $packageId,
            pppoeAccountId: null,
            hotspotUserId: null,
            routerId: null,
            networkDeviceId: null,
            monitoringAlertId: null,
            categoryId: $data->categoryId,
            priority: $data->priority,
            source: 'portal',
            subject: $data->subject,
            description: $data->description,
        );

        return $this->tickets->create($supportData, $userId)->toArray();
    }

    public function replyTicket(int $userId, int $ticketId, ReplyTicketData $data): array
    {
        $customerId = $this->requireCustomerId($userId);
        $this->validator->validateReply($data);

        $detail = $this->tickets->get($ticketId);
        if ((int) $detail['ticket']['customer_id'] !== $customerId) {
            throw new AuthorizationException('You do not have permission to reply to this ticket.');
        }

        $comment = new CreateCommentData(
            type: 'customer_reply',
            body: $data->body,
            customerId: $customerId,
        );

        return $this->tickets->comment($ticketId, $comment, $userId)->toArray();
    }

    public function requestTicketClosure(int $userId, int $ticketId): array
    {
        $customerId = $this->requireCustomerId($userId);
        $detail = $this->tickets->get($ticketId);

        if ((int) $detail['ticket']['customer_id'] !== $customerId) {
            throw new AuthorizationException('You do not have permission to close this ticket.');
        }

        return $this->tickets->transition($ticketId, 'resolved', $userId, 'Closure requested by customer via portal.')->toArray();
    }

    public function notifications(int $userId, array $query): array
    {
        $filters = NotificationListFilters::fromQuery($query);
        $result = $this->notifications->list($userId, $filters);

        return [
            'items' => array_map(static fn ($n) => $n->toArray(), $result['items']),
            'meta' => [
                'current_page' => $result['page'],
                'per_page' => $result['perPage'],
                'total' => $result['total'],
                'last_page' => $result['lastPage'],
            ],
        ];
    }

    public function markNotificationRead(int $userId, int $notificationId): array
    {
        return $this->notifications->markRead($notificationId, $userId)->toArray();
    }

    public function markAllNotificationsRead(int $userId): array
    {
        $count = $this->notifications->markAllRead($userId);

        return ['marked_read' => $count];
    }

    public function archiveNotification(int $userId, int $notificationId): array
    {
        return $this->notifications->archive($notificationId, $userId)->toArray();
    }

    public function preferences(int $userId): array
    {
        return $this->preferences->get($userId);
    }

    public function updatePreferences(int $userId, UpdatePreferenceData $data): array
    {
        $this->validator->validatePreferences($data);
        $preferenceData = new UserPreferenceData($data->preferences);

        return $this->preferences->update($userId, $preferenceData);
    }

    public function profile(int $userId): array
    {
        $customerId = $this->requireCustomerId($userId);

        return $this->customers->get($customerId)->toArray();
    }

    public function updateProfile(int $userId, UpdateProfileData $data): array
    {
        $customerId = $this->requireCustomerId($userId);
        $this->validator->validateProfile($data);

        $customer = $this->customers->get($customerId);
        $customerArray = $customer->toArray();

        $updateData = new UpdateCustomerData(
            fullName: $data->fullName ?? $customerArray['full_name'],
            fatherHusbandName: $customerArray['father_husband_name'] ?? null,
            cnic: $customerArray['cnic'] ?? null,
            phone: $data->phone ?? $customerArray['phone'],
            whatsapp: $data->whatsapp ?? $customerArray['whatsapp'] ?? null,
            email: $data->email ?? $customerArray['email'] ?? null,
            address: $data->address ?? $customerArray['address'],
            city: $data->city ?? $customerArray['city'],
            area: $data->area ?? $customerArray['area'],
            notes: $customerArray['notes'] ?? null,
            registrationDate: $customerArray['registration_date'] ?? null,
            installationDate: $customerArray['installation_date'] ?? null,
            installationTechnicianId: $customerArray['installation_technician_id'] ?? null,
            emergencyContactName: $data->emergencyContactName ?? $customerArray['emergency_contact_name'] ?? null,
            emergencyContactPhone: $data->emergencyContactPhone ?? $customerArray['emergency_contact_phone'] ?? null,
        );

        return $this->customers->update($customerId, $updateData, $userId, null, null)->toArray();
    }

    private function requireCustomerId(int $userId): int
    {
        $customerId = $this->users->findCustomerIdByUserId($userId);
        if ($customerId === null) {
            throw new AuthorizationException('No customer account is linked to this user.');
        }

        return $customerId;
    }

    private function findPrimaryConnection(int $customerId): ?\SkyFi\Connections\Models\Connection
    {
        $result = $this->connections->list(new ConnectionListFilters(
            customerId: $customerId,
            page: 1,
            perPage: 1,
        ));

        return $result['items'][0] ?? null;
    }

    private function latestInvoice(int $customerId): ?\SkyFi\Billing\Models\Invoice
    {
        $result = $this->invoices->list(InvoiceListFilters::fromQuery([
            'filter' => ['customer_id' => (string) $customerId],
            'sort' => '-created_at',
            'page' => ['number' => 1, 'size' => 1],
        ]));

        return $result['items'][0] ?? null;
    }

    /** @return array<int, \SkyFi\Payments\Models\Payment> */
    private function recentPayments(int $customerId): array
    {
        $result = $this->payments->list(PaymentListFilters::fromQuery([
            'filter' => ['customer_id' => (string) $customerId],
            'sort' => '-payment_date',
            'page' => ['number' => 1, 'size' => 5],
        ]));

        return $result['items'];
    }

    /** @return array<int, \SkyFi\Support\DomainModels\SupportTicket> */
    private function activeTickets(int $customerId): array
    {
        $result = $this->tickets->list(TicketListFilters::fromQuery([
            'filter' => ['customer_id' => (string) $customerId],
            'sort' => '-created_at',
            'page' => ['number' => 1, 'size' => 20],
        ]));

        $activeStatuses = ['new', 'open', 'assigned', 'in_progress', 'waiting_customer', 'escalated'];

        return array_values(array_filter(
            $result['items'],
            static fn ($ticket): bool => in_array($ticket->toArray()['status'] ?? '', $activeStatuses, true),
        ));
    }

    /** @return array<int, \SkyFi\Notifications\DomainModels\Notification> */
    private function recentNotifications(int $userId): array
    {
        $result = $this->notifications->list($userId, NotificationListFilters::fromQuery([
            'sort' => '-created_at',
            'page' => ['number' => 1, 'size' => 5],
        ]));

        return $result['items'];
    }

    private function calculateBalance(int $customerId): float
    {
        $statement = $this->pdo->prepare(
            'SELECT COALESCE(SUM(balance_due), 0.00) AS balance FROM invoices WHERE customer_id = :customer_id AND deleted_at IS NULL AND status NOT IN ("paid", "void", "cancelled")',
        );
        $statement->execute(['customer_id' => $customerId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return (float) ($row['balance'] ?? 0.00);
    }

    private function isCustomerOnline(int $customerId): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM connections WHERE customer_id = :customer_id AND status = "active" AND deleted_at IS NULL',
        );
        $statement->execute(['customer_id' => $customerId]);

        return (int) $statement->fetchColumn() > 0;
    }
}
