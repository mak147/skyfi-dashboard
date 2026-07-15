<?php

declare(strict_types=1);

namespace SkyFi\Notifications\DTOs;

final class DispatchNotificationData
{
    /**
     * @param list<int> $recipientUserIds
     * @param list<string> $channels
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly string $type,
        public readonly array $recipientUserIds,
        public readonly array $data = [],
        public readonly array $channels = ['in_app'],
        public readonly ?string $sourceModule = null,
        public readonly ?string $sourceEvent = null,
        public readonly ?string $sourceId = null,
        public readonly ?string $severity = null,
        public readonly ?string $actionUrl = null,
        public readonly ?int $actorId = null,
        public readonly ?string $eventUuid = null,
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        $attrs = $input['data']['attributes'] ?? $input;
        $recipients = $attrs['recipient_user_ids'] ?? $attrs['recipients'] ?? [];
        if (!is_array($recipients)) {
            $recipients = [];
        }
        $channels = $attrs['channels'] ?? ['in_app'];
        if (!is_array($channels)) {
            $channels = ['in_app'];
        }

        return new self(
            type: (string) ($attrs['type'] ?? $attrs['notification_type'] ?? ''),
            recipientUserIds: array_values(array_unique(array_map('intval', $recipients))),
            data: is_array($attrs['data'] ?? null) ? $attrs['data'] : [],
            channels: array_values(array_map('strval', $channels)),
            sourceModule: isset($attrs['source_module']) ? (string) $attrs['source_module'] : null,
            sourceEvent: isset($attrs['source_event']) ? (string) $attrs['source_event'] : null,
            sourceId: isset($attrs['source_id']) ? (string) $attrs['source_id'] : null,
            severity: isset($attrs['severity']) ? (string) $attrs['severity'] : null,
            actionUrl: isset($attrs['action_url']) ? (string) $attrs['action_url'] : null,
            actorId: isset($attrs['actor_id']) ? (int) $attrs['actor_id'] : null,
            eventUuid: isset($attrs['event_uuid']) ? (string) $attrs['event_uuid'] : null,
        );
    }
}
