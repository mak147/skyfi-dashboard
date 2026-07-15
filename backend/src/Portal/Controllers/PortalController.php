<?php

declare(strict_types=1);

namespace SkyFi\Portal\Controllers;

use SkyFi\Portal\Contracts\PortalServiceContract;
use SkyFi\Portal\DTOs\CreateTicketData;
use SkyFi\Portal\DTOs\ReplyTicketData;
use SkyFi\Portal\DTOs\UpdatePreferenceData;
use SkyFi\Portal\DTOs\UpdateProfileData;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class PortalController
{
    public function __construct(
        private readonly PortalServiceContract $service,
        private readonly RequirePermissionMiddleware $authorizer,
    ) {
    }

    private function userId(Request $request): int
    {
        return (int) ($request->attributes()['claims']['sub'] ?? 0);
    }

    private function authorize(int $userId, string $permission): void
    {
        $this->authorizer->authorize($userId, $permission);
    }

    private function routeId(Request $request): int
    {
        return (int) ($request->attributes()['route_params']['id'] ?? 0);
    }

    public function dashboard(Request $request): Response
    {
        $userId = $this->userId($request);
        $this->authorize($userId, 'portal.access');

        return ApiResponse::resource('portal-dashboard', (string) $userId, $this->service->dashboard($userId));
    }

    public function connection(Request $request): Response
    {
        $userId = $this->userId($request);
        $this->authorize($userId, 'view:service:own');

        return ApiResponse::resource('portal-connection', (string) $userId, $this->service->connection($userId));
    }

    public function invoices(Request $request): Response
    {
        $userId = $this->userId($request);
        $this->authorize($userId, 'view:invoice:own');

        $result = $this->service->invoices($userId, $request->query());

        return new Response(200, [
            'data' => array_map(
                static fn ($invoice): array => ['type' => 'invoices', 'id' => (string) $invoice['id'], 'attributes' => $invoice],
                $result['items'],
            ),
            'meta' => $result['meta'],
        ]);
    }

    public function invoice(Request $request): Response
    {
        $userId = $this->userId($request);
        $this->authorize($userId, 'view:invoice:own');

        return ApiResponse::resource('invoices', (string) $this->routeId($request), $this->service->invoice($userId, $this->routeId($request)));
    }

    public function invoicePdf(Request $request): Response
    {
        $userId = $this->userId($request);
        $this->authorize($userId, 'view:invoice:own');

        $invoiceId = $this->routeId($request);

        return ApiResponse::resource('invoice-pdfs', (string) $invoiceId, [
            'invoice_id' => $invoiceId,
            'pdf_status' => 'placeholder',
            'message' => 'Invoice PDF generation is reserved for the document service.',
            'preview_url' => '/api/v1/portal/invoices/' . $invoiceId . '/pdf',
        ]);
    }

    public function balance(Request $request): Response
    {
        $userId = $this->userId($request);
        $this->authorize($userId, 'view:invoice:own');

        return ApiResponse::resource('portal-balance', (string) $userId, $this->service->balance($userId));
    }

    public function payments(Request $request): Response
    {
        $userId = $this->userId($request);
        $this->authorize($userId, 'view:payment:own');

        $result = $this->service->payments($userId, $request->query());

        return new Response(200, [
            'data' => array_map(
                static fn ($payment): array => ['type' => 'payments', 'id' => (string) $payment['id'], 'attributes' => $payment],
                $result['items'],
            ),
            'meta' => $result['meta'],
        ]);
    }

    public function payment(Request $request): Response
    {
        $userId = $this->userId($request);
        $this->authorize($userId, 'view:payment:own');

        return ApiResponse::resource('payments', (string) $this->routeId($request), $this->service->payment($userId, $this->routeId($request)));
    }

    public function paymentReceipt(Request $request): Response
    {
        $userId = $this->userId($request);
        $this->authorize($userId, 'view:payment:own');

        $paymentId = $this->routeId($request);

        return ApiResponse::resource('payment-receipts', (string) $paymentId, [
            'payment_id' => $paymentId,
            'pdf_status' => 'placeholder',
            'message' => 'Receipt PDF generation is reserved for the document service.',
            'preview_url' => '/api/v1/portal/payments/' . $paymentId . '/receipt',
        ]);
    }

    public function tickets(Request $request): Response
    {
        $userId = $this->userId($request);
        $this->authorize($userId, 'view:ticket:own');

        $result = $this->service->tickets($userId, $request->query());

        return new Response(200, [
            'data' => array_map(
                static fn ($ticket): array => ['type' => 'support-tickets', 'id' => (string) $ticket['id'], 'attributes' => $ticket],
                $result['items'],
            ),
            'meta' => $result['meta'],
        ]);
    }

    public function ticket(Request $request): Response
    {
        $userId = $this->userId($request);
        $this->authorize($userId, 'view:ticket:own');

        return ApiResponse::resource('support-tickets', (string) $this->routeId($request), $this->service->ticket($userId, $this->routeId($request)));
    }

    public function createTicket(Request $request): Response
    {
        $userId = $this->userId($request);
        $this->authorize($userId, 'support.create');

        $data = CreateTicketData::fromArray($request->body());
        $ticket = $this->service->createTicket($userId, $data);

        return ApiResponse::resource('support-tickets', (string) $ticket['id'], $ticket, 201);
    }

    public function replyTicket(Request $request): Response
    {
        $userId = $this->userId($request);
        $this->authorize($userId, 'manage:ticket:own');

        $data = ReplyTicketData::fromArray($request->body());
        $comment = $this->service->replyTicket($userId, $this->routeId($request), $data);

        return ApiResponse::resource('ticket-comments', (string) $comment['id'], $comment, 201);
    }

    public function requestTicketClosure(Request $request): Response
    {
        $userId = $this->userId($request);
        $this->authorize($userId, 'manage:ticket:own');

        $ticket = $this->service->requestTicketClosure($userId, $this->routeId($request));

        return ApiResponse::resource('support-tickets', (string) $ticket['id'], $ticket);
    }

    public function notifications(Request $request): Response
    {
        $userId = $this->userId($request);
        $this->authorize($userId, 'notifications.view');

        $result = $this->service->notifications($userId, $request->query());

        return new Response(200, [
            'data' => array_map(
                static fn ($notification): array => ['type' => 'notifications', 'id' => (string) $notification['id'], 'attributes' => $notification],
                $result['items'],
            ),
            'meta' => $result['meta'],
        ]);
    }

    public function markNotificationRead(Request $request): Response
    {
        $userId = $this->userId($request);
        $this->authorize($userId, 'notifications.view');

        return ApiResponse::resource('notifications', (string) $this->routeId($request), $this->service->markNotificationRead($userId, $this->routeId($request)));
    }

    public function markAllNotificationsRead(Request $request): Response
    {
        $userId = $this->userId($request);
        $this->authorize($userId, 'notifications.view');

        return ApiResponse::resource('notification-bulk', (string) $userId, $this->service->markAllNotificationsRead($userId));
    }

    public function archiveNotification(Request $request): Response
    {
        $userId = $this->userId($request);
        $this->authorize($userId, 'notifications.view');

        return ApiResponse::resource('notifications', (string) $this->routeId($request), $this->service->archiveNotification($userId, $this->routeId($request)));
    }

    public function preferences(Request $request): Response
    {
        $userId = $this->userId($request);
        $this->authorize($userId, 'notifications.preferences');

        return ApiResponse::resource('notification-preferences', (string) $userId, $this->service->preferences($userId));
    }

    public function updatePreferences(Request $request): Response
    {
        $userId = $this->userId($request);
        $this->authorize($userId, 'notifications.preferences');

        $data = UpdatePreferenceData::fromArray($request->body());

        return ApiResponse::resource('notification-preferences', (string) $userId, $this->service->updatePreferences($userId, $data));
    }

    public function profile(Request $request): Response
    {
        $userId = $this->userId($request);
        $this->authorize($userId, 'view:customer:own');

        return ApiResponse::resource('customers', (string) $userId, $this->service->profile($userId));
    }

    public function updateProfile(Request $request): Response
    {
        $userId = $this->userId($request);
        $this->authorize($userId, 'update:customer:own');

        $data = UpdateProfileData::fromArray($request->body());

        return ApiResponse::resource('customers', (string) $userId, $this->service->updateProfile($userId, $data));
    }
}
