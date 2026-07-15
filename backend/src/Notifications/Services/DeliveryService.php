<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Services;

use SkyFi\Notifications\Contracts\ChannelDriverContract;
use SkyFi\Notifications\Contracts\DeliveryHistoryRepositoryContract;
use SkyFi\Notifications\Contracts\NotificationTemplateRepositoryContract;
use SkyFi\Notifications\Contracts\UserPreferenceRepositoryContract;
use SkyFi\Notifications\DomainModels\DeliveryHistory;

final class DeliveryService
{
    /** @var array<string, ChannelDriverContract> */
    private array $drivers = [];

    /**
     * @param list<ChannelDriverContract> $drivers
     */
    public function __construct(
        private readonly DeliveryHistoryRepositoryContract $deliveries,
        private readonly NotificationTemplateRepositoryContract $templates,
        private readonly UserPreferenceRepositoryContract $preferences,
        array $drivers,
    ) {
        foreach ($drivers as $driver) {
            $this->drivers[$driver->channel()] = $driver;
        }
    }

    /**
     * @param array<string, mixed> $context
     * @return list<DeliveryHistory>
     */
    public function deliver(
        string $type,
        string $category,
        int $recipientUserId,
        string $channel,
        array $context,
        bool $isTransactional,
        ?int $notificationId,
        ?int $eventId,
    ): array {
        $results = [];

        if (!$this->preferences->isChannelEnabled($recipientUserId, $channel, $category, $isTransactional)) {
            $results[] = $this->deliveries->create([
                'notification_id' => $notificationId,
                'event_id' => $eventId,
                'recipient_user_id' => $recipientUserId,
                'channel' => $channel,
                'status' => 'skipped',
                'provider' => $channel . '_stub',
                'fail_reason' => 'user_preference_disabled',
                'subject' => $context['subject'] ?? null,
                'body' => $context['body'] ?? null,
                'attempt_count' => 0,
            ]);

            return $results;
        }

        // Quiet hours apply to non-in-app non-transactional channels
        if ($channel !== 'in_app' && !$isTransactional && $this->inQuietHours($recipientUserId, $channel)) {
            $results[] = $this->deliveries->create([
                'notification_id' => $notificationId,
                'event_id' => $eventId,
                'recipient_user_id' => $recipientUserId,
                'channel' => $channel,
                'status' => 'skipped',
                'provider' => $channel . '_stub',
                'fail_reason' => 'quiet_hours',
                'subject' => $context['subject'] ?? null,
                'body' => $context['body'] ?? null,
                'attempt_count' => 0,
            ]);

            return $results;
        }

        $template = $this->templates->findByCodeChannel($type, $channel);
        $subject = $context['subject'] ?? null;
        $body = $context['body'] ?? null;
        $templateId = null;
        if ($template) {
            $templateId = $template->id();
            $vars = is_array($context['data'] ?? null) ? $context['data'] : [];
            $attrs = $template->toArray();
            $subject = $this->render((string) ($attrs['subject_template'] ?? $subject ?? ''), $vars);
            $body = $this->render((string) ($attrs['body_template'] ?? $body ?? ''), $vars);
        }

        $driver = $this->drivers[$channel] ?? null;
        $delivery = $this->deliveries->create([
            'notification_id' => $notificationId,
            'event_id' => $eventId,
            'recipient_user_id' => $recipientUserId,
            'channel' => $channel,
            'template_id' => $templateId,
            'status' => 'pending',
            'subject' => $subject,
            'body' => $body,
            'attempt_count' => 1,
        ]);

        if ($driver === null) {
            $results[] = $this->deliveries->update($delivery->id(), [
                'status' => 'failed',
                'fail_reason' => 'unknown_channel_driver',
                'provider' => $channel,
            ]);

            return $results;
        }

        $result = $driver->send([
            'subject' => $subject,
            'body' => $body,
            'notification_uuid' => $context['notification_uuid'] ?? null,
            'recipient_user_id' => $recipientUserId,
            'data' => $context['data'] ?? [],
        ]);

        $results[] = $this->deliveries->update($delivery->id(), [
            'status' => $result['status'],
            'provider' => $result['provider'],
            'provider_message_id' => $result['provider_message_id'],
            'fail_reason' => $result['fail_reason'],
            'subject' => $result['subject'] ?? $subject,
            'body' => $result['body'] ?? $body,
            'sent_at' => $result['status'] === 'sent' ? gmdate('Y-m-d H:i:s') : null,
        ]);

        return $results;
    }

    /** @param array<string, mixed> $vars */
    public function render(string $template, array $vars): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/',
            static function (array $matches) use ($vars): string {
                $key = $matches[1];
                $value = $vars[$key] ?? null;
                if ($value === null && str_contains($key, '.')) {
                    $parts = explode('.', $key);
                    $cursor = $vars;
                    foreach ($parts as $part) {
                        if (!is_array($cursor) || !array_key_exists($part, $cursor)) {
                            return '';
                        }
                        $cursor = $cursor[$part];
                    }
                    $value = $cursor;
                }

                if (is_scalar($value) || $value === null) {
                    return (string) ($value ?? '');
                }

                return json_encode($value, JSON_THROW_ON_ERROR);
            },
            $template
        );
    }

    private function inQuietHours(int $userId, string $channel): bool
    {
        $quiet = $this->preferences->quietHours($userId, $channel);
        if ($quiet === null || empty($quiet['start']) || empty($quiet['end'])) {
            return false;
        }

        try {
            $tz = new \DateTimeZone($quiet['timezone'] ?: 'Asia/Karachi');
        } catch (\Throwable) {
            $tz = new \DateTimeZone('Asia/Karachi');
        }
        $now = new \DateTimeImmutable('now', $tz);
        $start = \DateTimeImmutable::createFromFormat('H:i:s', $this->normalizeTime((string) $quiet['start']), $tz)
            ?: \DateTimeImmutable::createFromFormat('H:i', (string) $quiet['start'], $tz);
        $end = \DateTimeImmutable::createFromFormat('H:i:s', $this->normalizeTime((string) $quiet['end']), $tz)
            ?: \DateTimeImmutable::createFromFormat('H:i', (string) $quiet['end'], $tz);
        if (!$start || !$end) {
            return false;
        }

        $nowT = $now->format('H:i:s');
        $startT = $start->format('H:i:s');
        $endT = $end->format('H:i:s');

        if ($startT <= $endT) {
            return $nowT >= $startT && $nowT <= $endT;
        }

        // Overnight window
        return $nowT >= $startT || $nowT <= $endT;
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? $time . ':00' : $time;
    }
}
