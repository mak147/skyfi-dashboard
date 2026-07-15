<?php

declare(strict_types=1);

namespace SkyFi\Notifications\EventPublishers;

use SkyFi\Notifications\Contracts\NotificationEventRepositoryContract;
use SkyFi\Notifications\DomainModels\NotificationEvent;
use SkyFi\Shared\Events\EventDispatcher;

final class NotificationEventPublisher
{
    public function __construct(private readonly NotificationEventRepositoryContract $events) {}

    /**
     * @param array<string, mixed> $payload
     */
    public function record(
        string $eventKey,
        string $sourceModule,
        array $payload,
        ?string $sourceId = null,
        ?string $eventUuid = null,
    ): NotificationEvent {
        $uuid = $eventUuid ?: $this->uuid();
        $existing = $this->events->findByUuid($uuid);
        if ($existing) {
            return $existing;
        }

        return $this->events->create([
            'event_key' => $eventKey,
            'event_uuid' => $uuid,
            'source_module' => $sourceModule,
            'source_id' => $sourceId,
            'payload' => $payload,
            'status' => 'received',
        ]);
    }

    public function markProcessed(int $eventId): NotificationEvent
    {
        return $this->events->update($eventId, [
            'status' => 'processed',
            'processed_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    public function markFailed(int $eventId, string $message): NotificationEvent
    {
        return $this->events->update($eventId, [
            'status' => 'failed',
            'error_message' => $message,
            'processed_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    /** @param array<string, mixed> $payload */
    public function publishProcessed(string $eventKey, array $payload): void
    {
        EventDispatcher::dispatch('notifications.processed.' . $eventKey, $payload);
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
