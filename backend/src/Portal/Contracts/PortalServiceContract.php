<?php

declare(strict_types=1);

namespace SkyFi\Portal\Contracts;

use SkyFi\Portal\DTOs\CreateTicketData;
use SkyFi\Portal\DTOs\ReplyTicketData;
use SkyFi\Portal\DTOs\UpdatePreferenceData;
use SkyFi\Portal\DTOs\UpdateProfileData;

interface PortalServiceContract
{
    /** Returns the dashboard summary for the given user. */
    public function dashboard(int $userId): array;

    /** Returns the current customer's connection and package details. */
    public function connection(int $userId): array;

    /** Returns paginated invoices scoped to the customer. */
    public function invoices(int $userId, array $query): array;

    /** Returns a single invoice if it belongs to the customer. */
    public function invoice(int $userId, int $invoiceId): array;

    /** Returns the outstanding balance for the customer. */
    public function balance(int $userId): array;

    /** Returns paginated payments scoped to the customer. */
    public function payments(int $userId, array $query): array;

    /** Returns a single payment if it belongs to the customer. */
    public function payment(int $userId, int $paymentId): array;

    /** Returns paginated tickets scoped to the customer. */
    public function tickets(int $userId, array $query): array;

    /** Returns a single ticket if it belongs to the customer. */
    public function ticket(int $userId, int $ticketId): array;

    /** Creates a support ticket on behalf of the customer. */
    public function createTicket(int $userId, CreateTicketData $data): array;

    /** Adds a customer reply to a ticket. */
    public function replyTicket(int $userId, int $ticketId, ReplyTicketData $data): array;

    /** Requests closure of a customer ticket. */
    public function requestTicketClosure(int $userId, int $ticketId): array;

    /** Returns paginated notifications for the user. */
    public function notifications(int $userId, array $query): array;

    /** Marks a notification as read. */
    public function markNotificationRead(int $userId, int $notificationId): array;

    /** Marks all notifications as read. */
    public function markAllNotificationsRead(int $userId): array;

    /** Archives a notification. */
    public function archiveNotification(int $userId, int $notificationId): array;

    /** Returns notification preferences for the user. */
    public function preferences(int $userId): array;

    /** Updates notification preferences for the user. */
    public function updatePreferences(int $userId, UpdatePreferenceData $data): array;

    /** Returns the customer profile. */
    public function profile(int $userId): array;

    /** Updates the customer profile. */
    public function updateProfile(int $userId, UpdateProfileData $data): array;
}
