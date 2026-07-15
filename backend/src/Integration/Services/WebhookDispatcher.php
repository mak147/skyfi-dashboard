<?php

declare(strict_types=1);

namespace SkyFi\Integration\Services;

use SkyFi\Integration\Contracts\WebhookDeliveryRepositoryContract;
use SkyFi\Integration\Contracts\WebhookDispatcherContract;
use SkyFi\Integration\Contracts\WebhookRepositoryContract;
use SkyFi\Shared\Events\EventDispatcher;

final class WebhookDispatcher implements WebhookDispatcherContract
{
    public function __construct(
        private readonly WebhookRepositoryContract $webhooks,
        private readonly WebhookDeliveryRepositoryContract $deliveries,
        private readonly WebhookSignatureService $signer,
    ) {}

    public function dispatch(string $eventKey, array $payload): int
    {
        $matchingWebhooks = $this->webhooks->findActiveByEvent($eventKey);
        $dispatched = 0;

        foreach ($matchingWebhooks as $webhook) {
            $webhookAttrs = $webhook->toArrayWithSecrets();

            // Apply filter rules if present
            if (isset($webhookAttrs['filter_rules']) && is_array($webhookAttrs['filter_rules']) && !$this->passesFilter($payload, $webhookAttrs['filter_rules'])) {
                continue;
            }

            $signature = $this->signer->sign($payload, $webhookAttrs['secret']);
            $headers = [
                'Content-Type' => $webhookAttrs['content_type'] ?? 'application/json',
                'X-SkyFi-Event' => $eventKey,
                'X-SkyFi-Signature' => 'sha256=' . $signature,
                'X-SkyFi-Delivery' => $this->uuid(),
            ];

            $this->deliveries->create([
                'webhook_id' => $webhook->id(),
                'event_key' => $eventKey,
                'payload' => $payload,
                'request_headers' => $headers,
                'status' => 'pending',
                'attempt_number' => 1,
            ]);

            $dispatched++;
        }

        return $dispatched;
    }

    public function retryDelivery(int $deliveryId): bool
    {
        $delivery = $this->deliveries->find($deliveryId);
        if ($delivery === null) {
            return false;
        }

        $attrs = $delivery->toArray();
        $webhook = $this->webhooks->find((int) ($attrs['webhook_id'] ?? 0));
        if ($webhook === null || !$webhook->toArray()['is_active']) {
            return false;
        }

        $webhookAttrs = $webhook->toArrayWithSecrets();
        $payload = $attrs['payload'] ?? [];
        $signature = $this->signer->sign($payload, $webhookAttrs['secret']);
        $attempt = (int) ($attrs['attempt_number'] ?? 1) + 1;
        $retryPolicy = $webhookAttrs['retry_policy'] ?? ['max_attempts' => 3, 'backoff' => 'exponential'];
        $maxAttempts = (int) ($retryPolicy['max_attempts'] ?? 3);

        if ($attempt > $maxAttempts) {
            $this->deliveries->update($deliveryId, [
                'status' => 'failed',
                'error_message' => 'Max retry attempts reached.',
            ]);

            return false;
        }

        $nextRetry = $this->calculateNextRetry($attempt, $retryPolicy);

        $this->deliveries->update($deliveryId, [
            'status' => 'retrying',
            'attempt_number' => $attempt,
            'next_retry_at' => $nextRetry,
            'request_headers' => [
                'Content-Type' => $webhookAttrs['content_type'] ?? 'application/json',
                'X-SkyFi-Event' => $attrs['event_key'] ?? '',
                'X-SkyFi-Signature' => 'sha256=' . $signature,
                'X-SkyFi-Delivery' => $this->uuid(),
            ],
        ]);

        return true;
    }

    public function processRetryQueue(): int
    {
        $pending = $this->deliveries->findPendingRetries();
        $processed = 0;

        foreach ($pending as $delivery) {
            $attrs = $delivery->toArray();
            $webhook = $this->webhooks->find((int) ($attrs['webhook_id'] ?? 0));
            if ($webhook === null || !$webhook->toArray()['is_active']) {
                $this->deliveries->update($delivery->id(), ['status' => 'failed', 'error_message' => 'Webhook no longer active.']);
                continue;
            }

            // Mark as pending again for next dispatch cycle
            $this->deliveries->update($delivery->id(), ['status' => 'pending']);
            $processed++;
        }

        return $processed;
    }

    public function handleInbound(string $eventType, array $payload, string $signature): array
    {
        $matchingWebhooks = $this->webhooks->findInboundByEventType($eventType);
        $accepted = false;

        foreach ($matchingWebhooks as $webhook) {
            $webhookAttrs = $webhook->toArrayWithSecrets();
            $inboundSecret = $webhookAttrs['inbound_secret'] ?? '';
            if ($inboundSecret !== '' && $this->signer->verify($payload, $inboundSecret, $signature)) {
                $accepted = true;
                // Re-dispatch inbound events into the system
                EventDispatcher::dispatch('integration.inbound.' . $eventType, $payload);
                break;
            }
        }

        if (!$accepted) {
            return ['accepted' => false, 'reason' => 'No matching inbound webhook with valid signature.'];
        }

        return ['accepted' => true, 'event_type' => $eventType];
    }

    /** @param array<string, mixed> $payload @param array<string, mixed> $rules */
    private function passesFilter(array $payload, array $rules): bool
    {
        // Simple filter: each key in rules must match the payload value
        foreach ($rules as $key => $expectedValue) {
            $actualValue = $payload[$key] ?? null;
            if (is_array($expectedValue)) {
                if (!in_array($actualValue, $expectedValue, true)) {
                    return false;
                }
            } elseif ($actualValue !== $expectedValue) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, mixed> $retryPolicy */
    private function calculateNextRetry(int $attempt, array $retryPolicy): string
    {
        $backoff = $retryPolicy['backoff'] ?? 'exponential';
        if ($backoff === 'exponential') {
            $seconds = (int) pow(2, $attempt) * 60; // 2 min, 4 min, 8 min...
        } else {
            $seconds = $attempt * 300; // Linear: 5 min intervals
        }

        return gmdate('Y-m-d H:i:s', time() + $seconds);
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
