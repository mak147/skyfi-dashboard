<?php

declare(strict_types=1);

namespace SkyFi\Integration\Contracts;

interface WebhookDispatcherContract
{
    /** @param array<string, mixed> $payload */
    public function dispatch(string $eventKey, array $payload): int;

    public function retryDelivery(int $deliveryId): bool;

    public function processRetryQueue(): int;

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    public function handleInbound(string $eventType, array $payload, string $signature): array;
}
