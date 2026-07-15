<?php

declare(strict_types=1);

namespace SkyFi\Integration\Controllers;

use SkyFi\Integration\Contracts\WebhookDispatcherContract;
use SkyFi\Shared\Http\Response;

final class InboundWebhookController
{
    public function __construct(
        private readonly WebhookDispatcherContract $dispatcher,
    ) {}

    /**
     * Handle an inbound webhook POST.
     * Expects JSON body with 'event_type' and 'payload', plus 'X-SkyFi-Signature' header.
     */
    public function handle(\SkyFi\Shared\Http\Request $r): Response
    {
        $body = $r->body();
        $eventType = (string) ($body['event_type'] ?? '');
        $payload = (array) ($body['payload'] ?? $body);
        $signature = str_replace('sha256=', '', (string) ($r->header('X-SkyFi-Signature') ?? ''));

        if ($eventType === '') {
            return new Response(400, [
                'errors' => [['status' => '400', 'title' => 'Bad Request', 'detail' => 'event_type is required.']],
            ]);
        }

        $result = $this->dispatcher->handleInbound($eventType, $payload, $signature);

        if (!$result['accepted']) {
            return new Response(403, [
                'errors' => [['status' => '403', 'title' => 'Forbidden', 'detail' => $result['reason']]],
            ]);
        }

        return new Response(200, [
            'data' => ['type' => 'inbound-webhooks', 'id' => $eventType, 'attributes' => $result],
        ]);
    }
}
